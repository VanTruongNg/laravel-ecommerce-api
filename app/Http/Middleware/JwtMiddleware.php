<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

//Middleware để xác thực JWT ( request -> middleware -> route -> controller)
class JwtMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = str_replace('Bearer ', '', $request->header('Authorization'));
            
            if (!$token) {
                return response()->json(['error' => 'Unauthorized - No token provided'], 401);
            }

            $sessionId = $request->cookie('session_id');

            if (!$sessionId) {
                return response()->json(['error' => 'Unauthorized - No session found'], 401);
            }

            //Kiểm tra xem phiên có tồn tại trong Redis
            $sessionExists = Redis::exists($sessionId);
            if (!$sessionExists) {
                return response()->json(['error' => 'Unauthorized - Invalid session'], 401);
            }

            //Lấy dữ liệu phiên từ Redis
            $sessionData = Redis::hgetall($sessionId);
            if ($sessionData['is_revoked'] === 'true') {
                return response()->json(['error' => 'Unauthorized - Session revoked'], 401);
            }

            try {
                $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

                //Kiểm tra thời gian hết hạn của token
                if (isset($decoded->exp) && time() > $decoded->exp) {
                    return response()->json(['error' => 'Token has expired'], 401);
                }

                // Kiểm tra xem token có chứa user ID không
                if (!isset($decoded->sub)) {
                    return response()->json(['error' => 'Invalid token format - Missing user ID'], 401);
                }

                //Thêm thông tin vào request
                $request->auth = $decoded;
                $request->session_data = $sessionData;
                
                return $next($request);
            } catch (Exception $e) {
                \Log::error('JWT Middleware - Token validation error', [
                    'error' => $e->getMessage(),
                    'session_id' => $sessionId,
                    'token' => substr($token, 0, 10) . '...' // Log only part of token for security
                ]);
                return response()->json(['error' => 'Invalid token'], 401);
            }
        } catch (Exception $e) {
            \Log::error('JWT Middleware - General error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Authorization error: ' . $e->getMessage()], 500);
        }
    }
}