<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UserRole;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->auth) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Lấy user từ database dựa vào JWT payload
        $user = \App\Models\User::find($request->auth->sub);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 401);
        }

        $requiredRole = UserRole::from($role);

        if (!$user->hasRole($requiredRole)) {
            return response()->json(['message' => 'Permission denied'], 403);
        }

        // Set user vào request để các controller có thể sử dụng
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}