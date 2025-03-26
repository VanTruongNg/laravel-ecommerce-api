<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
            
            if (!$token) {
                return response()->json(['error' => 'Unauthorized - No token provided'], 401);
            }
            
            try {
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

                //Kiểm tra thời gian hết hạn của token
                if (isset($decoded->exp) && time() > $decoded->exp) {
                    return response()->json(['error' => 'Token has expired'], 401);
                }

                // Thêm thông tin decoded JWT vào request
                $request->auth = $decoded;

                // Kiểm tra session nếu có
                $sessionId = $request->cookie('session_id');
                if (!$sessionId) {
                    return response()->json(['error' => 'Unauthorized - No session ID provided'], 401);
                }
                
                //Kiểm tra xem phiên có tồn tại trong Redis
                $sessionExists = Redis::exists($sessionId);
                if (!$sessionExists) {
                    Log::warning('Invalid session', ['session_id' => $sessionId]);
                    return response()->json(['error' => 'Unauthorized - Invalid session'], 401);
                }

                //Lấy dữ liệu phiên từ Redis
                $sessionData = Redis::hgetall($sessionId);
                if ($sessionData['is_revoked'] === 'true') {
                    Log::warning('Session revoked', ['session_id' => $sessionId]);
                    return response()->json(['error' => 'Unauthorized - Session revoked'], 401);
                }

                $request->session_data = $sessionData;
                
                return $next($request);
            } catch (Exception $e) {
                Log::error('JWT Middleware - Token validation error', [
                    'error' => $e->getMessage(),
                    'session_id' => $request->cookie('session_id'),
                    'token' => substr($token, 0, 10) . '...' // Log only part of token for security
                ]);
                return response()->json(['error' => 'Invalid token'], 401);
            }
        } catch (Exception $e) {
            Log::error('JWT Middleware - General error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Authorization error: ' . $e->getMessage()], 500);
        }
    }
}
