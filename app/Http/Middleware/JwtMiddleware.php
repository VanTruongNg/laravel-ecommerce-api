<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $authHeader = $request->header('Authorization');
            if (!$authHeader) {
                return response()->json(['error' => 'Unauthorized - No Authorization header provided'], 401);
            }

            if (!str_starts_with($authHeader, 'Bearer ')) {
                return response()->json(['error' => 'Unauthorized - Invalid Authorization header format'], 401);
            }

            $token = str_replace('Bearer ', '', $authHeader);

            try {
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET') ?? '', 'HS256'));

                if (isset($decoded->exp) && time() > $decoded->exp) {
                    Log::warning('JWT Middleware - Token expired', [
                        'exp' => $decoded->exp,
                        'current_time' => time()
                    ]);
                    return response()->json(['error' => 'Token has expired'], 401);
                }

                $isBlacklist = Redis::get("blacklist:" . $decoded->jti);
                if ($isBlacklist) {
                    return response()->json(['error' => 'Token has revoked'], 401);
                }

                $request->auth = $decoded;
                
                return $next($request);

            } catch (Exception $e) {
                return response()->json([
                    'error' => 'Invalid token',
                    'message' => $e->getMessage()
                ], 401);
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Authorization error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
