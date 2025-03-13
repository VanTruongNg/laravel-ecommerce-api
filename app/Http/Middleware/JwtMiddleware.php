<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            \Log::info('JWT Middleware - Start');
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
            \Log::info('JWT Middleware - Token received', ['token' => $token]);
            
            if (!$token) {
                \Log::warning('JWT Middleware - No token provided');
                return response()->json(['error' => 'Unauthorized - No token provided'], 401);
            }

            try {
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
                \Log::info('JWT Middleware - Token decoded successfully', ['user_id' => $decoded->user_id]);
                $request->auth = $decoded;
                return $next($request);
            } catch (Exception $e) {
                \Log::error('JWT Middleware - Token validation error', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Invalid token: ' . $e->getMessage()], 401);
            }
        } catch (Exception $e) {
            \Log::error('JWT Middleware - General error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Authorization error: ' . $e->getMessage()], 500);
        }
    }
}