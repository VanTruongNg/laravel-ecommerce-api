<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends Controller
{
    private function generateJWTToken($user)
    {
        $payload = [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'iat' => time(),
            'exp' => time() + (60 * 60) // Token expires in 1 hour
        ];

        return JWT::encode($payload, env('JWT_SECRET'), 'HS256');
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

            $token = $this->generateJWTToken($user);

            return response()->json([
                'user' => $user,
                'access_token' => $token
            ], 201);

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

            return response()->json([
                'user' => $user,
                'access_token' => $token,
            ]);
        } catch (\Exception $e) {
            \Log::error('Login error: ' . $e->getMessage());
            return response()->json(['error' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }

    public function user(Request $request)
    {
        try {
            \Log::info('AuthController - User method start');
            
            if (!$request->auth) {
                \Log::error('AuthController - Auth data not found in request');
                return response()->json(['error' => 'Auth data not found'], 500);
            }
            
            $decoded = $request->auth;
            \Log::info('AuthController - Auth data retrieved', ['user_id' => $decoded->user_id]);
            
            $user = User::find($decoded->user_id);
            \Log::info('AuthController - Database query executed', ['found' => (bool)$user]);

            if (!$user) {
                \Log::warning('AuthController - User not found', ['user_id' => $decoded->user_id]);
                return response()->json(['error' => 'User not found'], 404);
            }

            \Log::info('AuthController - User found successfully');
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
