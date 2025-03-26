<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Session;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Firebase\JWT\JWT;
use Symfony\Component\Uid\UuidV4;
use App\utils\Response;

class AuthController extends Controller
{
    private function generateJWTToken($user)
    {
        $accessPayload = [
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role->value,
            'iat' => time(),
            'exp' => time() + (60 * 15),
            'jti' => UuidV4::v4()
        ];

        $refreshPayload = [
            'sub' => $user->id,
            'type' => 'refresh',
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

            $token = $this->generateJWTToken($user);

            $session = new Session([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_activity' => now()->timestamp,
                'payload' => json_encode([
                    'device' => $request->userAgent(),
                    'login_time' => now()->toISOString()
                ])
            ]);
            $session->save();

            $sessionId = $session->id;

            Redis::hmset($sessionId, [
                'user_id' => $user->id,
                'refresh_token' => $token['refresh_token'],
                'is_revoked' => 'false'
            ]);
            Redis::expire($sessionId, 60 * 24 * 7);

            $response = Response::success(
                'Login successful',
                [
                    'user' => $user,
                    'access_token' => $token['access_token']
                ]
            );

            return $response->cookie(
                'session_id',
                $session->id,
                60 * 24 * 7,
                '/',
                null,
                false,
                true,
                false,
                'Strict'
            );
        } catch (\Exception $e) {
            return Response::serverError(
                'Login failed',
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
            $sessionId = $request->cookie('session_id');

            if ($sessionId) {
                Redis::del($sessionId);

                $session = Session::find($sessionId);
                if ($session) {
                    $session->delete();
                }
            }

            $response = Response::success('Successfully logged out');

            return $response->cookie('session_id', '', -1);
        } catch (\Exception $e) {
            return Response::serverError(
                'Logout failed',
                $e->getMessage()
            );
        }
    }
}
