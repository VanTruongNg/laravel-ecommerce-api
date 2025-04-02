<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Date;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\Uid\UuidV4;
use App\utils\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function generateJWTToken($user, $sessionId)
    {
        $accessPayload = [
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + (60 * 15),
            'jti' => UuidV4::v4()
        ];

        $refreshPayload = [
            'sid' => $sessionId,
            'sub' => $user->id,
            'type' => 'refresh',
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7),
            'jti' => UuidV4::v4()
        ];

        return [
            'access_token' => JWT::encode($accessPayload, env('JWT_SECRET'), 'HS256'),
            'refresh_token' => JWT::encode($refreshPayload, env('JWT_REFRESH_SECRET'), 'HS256')
        ];
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'password' => 'required|string|confirmed|min:6',
                'email' => 'required|string|email|unique:users'
            ])->stopOnFirstFailure();

            if ($validator->fails()) {
                return Response::validationError(
                    'Validation failed',
                    $validator->errors()
                );
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            return Response::created(
                'User registered successfully',
                ['user' => $user]
            );
        } catch (\Exception $e) {
            return Response::serverError(
                'Registration failed',
                $e->getMessage()
            );
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ])->stopOnFirstFailure();

            if ($validator->fails()) {
                return Response::validationError(
                    'Validation failed',
                    $validator->errors()
                );
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return Response::unauthorized(
                    'Invalid credentials'
                );
            }

            $sessionId = UuidV4::v4();

            $token = $this->generateJWTToken($user, $sessionId);

            $sessionKey = "session:" . $sessionId;
            Redis::hmset($sessionKey, [
                'user_id' => $user->id,
                'refresh_token' => $token['refresh_token'],
                'is_revoked' => 'false',
                'device' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'last_activity' => now()->timestamp
            ]);
            Redis::expire($sessionKey, 60 * 24 * 7);

            $response = Response::success(
                'Login successful',
                [
                    'user' => $user,
                    'access_token' => $token['access_token']
                ]
            );

            return $response->cookie(
                'refresh_token',
                $token['refresh_token'],
                60 * 24 * 7,
                '/',
                null,
                false,
                true,
                false,
                'Lax'
            );
        } catch (\Exception $e) {
            return Response::serverError(
                'Login failed',
                $e->getMessage()
            );
        }
    }

    public function googleLogin()
    {
        return response()->json([
            'url' => Socialite::driver('google')->stateless()->redirect()->getTargetUrl()
        ]);
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            config([
                'services.google.guzzle.verify' => false
            ]);

            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'avatarUrl' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now()
                ]);
            } else {
                if (!$user->email_verified_at) {
                    $user->update(['email_verified_at' => now()]);
                }
                if (!$user->avatarUrl) {
                    $user->update(['avatarUrl' => $googleUser->getAvatar()]);
                }
            }

            $sessionId = UuidV4::v4();
            $token = $this->generateJWTToken($user, $sessionId);

            $sessionKey = "session:" . $sessionId;
            Redis::hmset($sessionKey, [
                'user_id' => $user->id,
                'refresh_token' => $token['refresh_token'],
                'is_revoked' => 'false',
                'device' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'last_activity' => now()->timestamp
            ]);
            Redis::expire($sessionKey, 60 * 24 * 7);

            return redirect()->away(env('FRONTEND_URL') . '/auth/google/success?access_token=' . $token['access_token'] . '&refresh_token=' . $token['refresh_token']);
        } catch (\Exception $e) {
            return Response::serverError(
                'Google login failed',
                $e->getMessage()
            );
        }
    }

    public function user(Request $request)
    {
        try {
            if (!$request->auth) {
                return Response::unauthorized(
                    'Auth data not found'
                );
            }

            $decoded = $request->auth;
            $user = User::find($decoded->sub);

            if (!$user) {
                return Response::notFound(
                    'User not found'
                );
            }

            return Response::success(
                'User profile retrieved successfully',
                ['user' => $user]
            );
        } catch (\Exception $e) {
            return Response::serverError(
                'Failed to get user profile',
                $e->getMessage()
            );
        }
    }

    public function refresh(Request $request)
    {
        // Implementation pending
    }

    public function logout(Request $request)
    {
        try {
            $refreshToken = $request->cookie('refresh_token');
            $authHeader = $request->header('Authorization');

            if (!$authHeader) {
                return Response::unauthorized('Access token is required');
            }

            if (!$refreshToken) {
                return Response::unauthorized('Refresh token is required');
            }

            try {
                $decodedRefresh = JWT::decode($refreshToken, new Key(env('JWT_REFRESH_SECRET'), 'HS256'));
                if (isset($decodedRefresh->sid)) {
                    $sessionKey = "session:" . $decodedRefresh->sid;
                    Redis::del($sessionKey);
                }

                $accessToken = str_replace('Bearer ', '', $authHeader);
                $decodedAccess = JWT::decode($accessToken, new Key(env('JWT_SECRET'), 'HS256'));

                $remainingTime = $decodedAccess->exp - time();
                if ($remainingTime > 0) {
                    $blacklistKey = "blacklist:" . $decodedAccess->jti;
                    Redis::setex($blacklistKey, $remainingTime, 'true');
                }

                $response = Response::success('Successfully logged out');
                return $response->cookie('refresh_token', '', -1);

            } catch (\Exception $e) {
                return Response::unauthorized('Invalid tokens');
            }

        } catch (\Exception $e) {
            return Response::serverError(
                'Logout failed',
                $e->getMessage()
            );
        }
    }
}
