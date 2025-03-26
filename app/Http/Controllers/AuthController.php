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

class AuthController extends Controller
{
    private function generateJWTToken($user)
    {
        $accessPayload = [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'iat' => time(),
            'exp' => time() + (60 * 15),
            'jti' => UuidV4::v4()
        ];

        $refreshPayload = [
            'user_id' => $user->id,
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
                'email' => 'required|string|email|unique:users',
                'password' => 'required|string|confirmed|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 400);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
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

            return response()->json([
                'user' => $user,
                'access_token' => $token['access_token'],
            ])->cookie('session_id', $session->id, 60 * 24 * 7, '/', null, false, true, false, 'None');
        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json(['error' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }

    public function user(Request $request)
    {
        try {

            if (!$request->auth) {
                return response()->json(['error' => 'Auth data not found'], 500);
            }

            $decoded = $request->auth;

            $user = User::find($decoded->user_id);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            return response()->json(['user' => $user]);
        } catch (\Exception $e) {
            \Log::error('AuthController - User profile error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to get user profile: ' . $e->getMessage()], 500);
        }
    }

    public function refresh(Request $request)
    {
    }

    public function logout()
    {
        // Since JWT is stateless, we don't need to do anything server-side
        // The client should remove the token from storage
        return response()->json(['message' => 'Successfully logged out']);
    }
}
