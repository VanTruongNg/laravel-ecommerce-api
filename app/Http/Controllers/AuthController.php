<?php

namespace App\Http\Controllers;

use App\Mail\ResetPasswordEmail;

use App\Http\Controllers\Controller;
use App\Mail\VerificationEmail;
use App\Models\Cart;
use App\Models\Token;
use App\Models\User;
use App\utils\Response;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\Uid\UuidV4;

class AuthController extends Controller
{
    private function generateJWTToken($user, $userAgent, $ipAddress)
    {
        $accessJTI = UuidV4::v4();
        $sessionId = UuidV4::v4();

        $accessPayload = [
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->full_name,
            'role' => $user->role,
            'type' => 'access',
            'iat' => time(),
            'exp' => time() + (60 * 15),
            'jti' => $accessJTI
        ];

        $refreshPayload = [
            'sid' => $sessionId,
            'sub' => $user->id,
            'type' => 'refresh',
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7),
        ];

        $access_token = JWT::encode($accessPayload, env('JWT_SECRET'), 'HS256');
        $refresh_token = JWT::encode($refreshPayload, env('JWT_REFRESH_SECRET'), 'HS256');

        $sessionKey = "session:" . $sessionId;
        Redis::hmset($sessionKey, [
            'user_id' => $user->id,
            'refresh_token' => $refresh_token,
            'access_token_jti' => $accessJTI,
            'is_revoked' => 'false',
            'device' => $userAgent,
            'ip_address' => $ipAddress,
            'last_activity' => now()->timestamp
        ]);
        Redis::expire($sessionKey, 60 * 24 * 7);

        return [
            'access_token' => $access_token,
            'refresh_token' => $refresh_token
        ];
    }

    private function createVerificationToken($user)
    {
        $token = '';
        do {
            $token = str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (Token::where('token', $token)->exists());

        return Token::create([
            'user_id' => $user->id,
            'token' => $token,
            'type' => 'email_verification',
            'expires_at' => now()->addMinutes(15),
            'is_valid' => true
        ]);
    }

    private function createPasswordResetToken($user)
    {
        $token = '';
        do {
            $token = str_pad(random_int(0, 9999999), 7, '0', STR_PAD_LEFT);
        } while (Token::where('token', $token)->exists());

        return Token::create([
            'user_id' => $user->id,
            'token' => $token,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(15),
            'is_valid' => true
        ]);
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'full_name' => 'required|string',
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
                'full_name' => $request->input('full_name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role' => 'customer',
                'avatar_url' => null,
                'phone' => null,
                'address' => null,
                'email_verified_at' => null
            ]);

            Cart::create([
                'user_id' => $user->id,
            ]);

            $token = $this->createVerificationToken($user);

            Mail::to($user->email)->queue(new VerificationEmail($user, $token));

            return Response::created(
                'User registered successfully. Please check your email to verify your account.',
                [
                    'user' => $user
                ]
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

            if ($user->email_verified_at === null) {
                return Response::unauthorized(
                    'Email not verified'
                );
            }

            $token = $this->generateJWTToken($user, $request->userAgent(), $request->ip());

            $response = Response::success(
                'Login successful',
                [
                    'user' => $user,
                    'access_token' => $token['access_token'],
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

    public function verifyEmail(Request $request, $token)
    {
        try {
            if (!$token) {
                return Response::notFound(
                    'Verification token is required'
                );
            }

            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
            ])->stopOnFirstFailure();

            if ($validator->fails()) {
                return Response::validationError(
                    'Validation failed',
                    $validator->errors()
                );
            }

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return Response::notFound(
                    'User not found'
                );
            }

            $verificationToken = Token::where('token', $token)
                ->where('type', 'email_verification')
                ->where('user_id', $user->id)
                ->where('expires_at', '>', Carbon::now())->first();

            if (!$verificationToken) {
                return Response::notFound(
                    'Verification token not found or expired'
                );
            }

            $user->email_verified_at = Carbon::now();
            $user->save();

            $verificationToken->delete();

            return Response::success(
                'Email verified successfully',
            );
        } catch (\Exception $e) {
            Log::error('Email verification error: ' . $e->getMessage());
            return Response::serverError(
                'Email verification failed',
                $e->getMessage()
            );
        }
    }

    public function resendVerificationToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => "required|string|email"
            ])->stopOnFirstFailure();

            if ($validator->fails()) {
                return Response::validationError(
                    'Validation failed',
                    $validator->errors()
                );
            }

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return Response::notFound(
                    'User not found'
                );
            }
            if ($user->email_verified_at) {
                return Response::unauthorized(
                    'Email already verified'
                );
            }

            $verificationToken = Token::where('user_id', $user->id)
                ->where('type', 'email_verification')
                ->where('expires_at', '>', Carbon::now())->first();
            if ($verificationToken) {
                Mail::to($user->email)->queue(new VerificationEmail($user, $verificationToken));
                return Response::success(
                    'Verification token resent successfully',
                );
            }

            $newToken = $this->createVerificationToken($user);
            Mail::to($user->email)->queue(new VerificationEmail($user, $newToken));

            return Response::success(
                'New verification token sent successfully',
            );
        } catch (\Exception $e) {
            return Response::serverError(
                'Resend verification token failed',
                $e->getMessage()
            );
        }
    }

    public function sendPasswordResetToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "email" => "required|string|email"
            ])->stopOnFirstFailure();

            if ($validator->fails()) {
                return Response::validationError(
                    'Validation failed',
                    $validator->errors()
                );
            }

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return Response::notFound(
                    'User not found'
                );
            }

            $passwordResetToken = Token::where('user_id', $user->id)
                ->where('type', 'password_reset')
                ->where('expires_at', '>', Carbon::now())->first();

            if ($passwordResetToken) {
                Mail::to($user->email)->queue(new ResetPasswordEmail($user, $passwordResetToken));
                return Response::success(
                    'Password reset token resent successfully',
                );
            }

            $passwordResetToken = $this->createPasswordResetToken($user);
            Mail::to($user->email)->queue(new ResetPasswordEmail($user, $passwordResetToken));

            return Response::success(
                'Password reset token sent successfully',
            );
        } catch (\Exception $e) {
            return Response::serverError(
                'Send password reset token failed',
                $e->getMessage()
            );
        }
    }

    public function resetPassword(Request $request, $token)
    {
        try {
            if (!$token) {
                return Response::notFound(
                    'Reset Password Token is required'
                );
            }

            $validator = Validator::make($request->all(), [
                "email" => "required|string|email",
                "password" => "required|string|confirmed|min:6"
            ])->stopOnFirstFailure();

            if ($validator->fails()) {
                return Response::validationError(
                    'Validation failed',
                    $validator->errors()
                );
            }

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return Response::notFound(
                    'User not found'
                );
            }

            $passwordResetToken = Token::where('token', $token)
                ->where('type', 'password_reset')
                ->where('user_id', $user->id)
                ->where('expires_at', '>', Carbon::now())->first();

            if (!$passwordResetToken) {
                return Response::notFound(
                    'Password reset token not found or expired'
                );
            }

            $user->password = Hash::make($request->password);
            $user->email_verified_at = $user->email_verified_at ?? Carbon::now();
            $passwordResetToken->delete();
            $user->save();

            return Response::success(
                'Password reset successfully',
            );
        } catch (\Exception $e) {
            return Response::serverError(
                'Reset password failed',
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
                    'full_name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'avatar_url' => $googleUser->getAvatar(),
                    'password' => Hash::make(Str::random(16)),
                    'email_verified_at' => now(),
                    'role' => 'customer',
                    'phone' => null,
                    'address' => null
                ]);
            } else {
                $updates = [];
                if (!$user->email_verified_at) {
                    $updates['email_verified_at'] = now();
                }
                if (!$user->avatar_url) {
                    $updates['avatar_url'] = $googleUser->getAvatar();
                }
                if (!empty($updates)) {
                    $user->update($updates);
                }
            }

            $token = $this->generateJWTToken($user, $request->userAgent(), $request->ip());

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
        try {
            $refreshToken = $request->cookie('refresh_token');
            if (!$refreshToken) {
                return Response::unauthorized('Refresh token is required');
            }

            $decodedRefresh = JWT::decode($refreshToken, new Key(env('JWT_REFRESH_SECRET'), 'HS256'));
            if (!isset($decodedRefresh->sid)) {
                return Response::unauthorized('Invalid refresh token');
            }

            $sessionKey = "session:" . $decodedRefresh->sid;
            $sessionData = Redis::hgetall($sessionKey);
            if (!$sessionData) {
                return Response::unauthorized('Session not found');
            }

            $user = User::find($decodedRefresh->sub);
            if (!$user) {
                return Response::notFound('User not found');
            }

            $newSessionKey = "blacklist:" . $sessionData["access_token_jti"];
            Redis::setex($newSessionKey, 15 * 60, 'true');

            Redis::del($sessionKey);

            $token = $this->generateJWTToken($user, $request->userAgent(), $request->ip());

            $response = Response::success(
                'Token refreshed successfully',
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
                'Token refresh failed',
                $e->getMessage()
            );
        }
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
