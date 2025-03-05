<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AuthController extends Controller
{
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

            try {
                $token = $user->createToken('auth_token')->plainTextToken;
            } catch (\Exception $e) {
                \Log::error('Token creation failed: ' . $e->getMessage());
                // Return user data even if token creation fails
                return response()->json([
                    'user' => $user,
                    'message' => 'User registered successfully but token creation failed'
                ], 201);
            }

            return response()->json(['user' => $user, 'token' => $token], 201);

        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
            return response()->json(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
    }

    public function user(Request $request)
    {
    }

    public function logout(Request $request)
    {
    }
}
