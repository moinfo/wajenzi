<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check for Authorization header with Bearer token
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Authorization header missing or invalid'], 401);
        }
        
        // Extract the token
        $token = substr($authHeader, 7); // Remove 'Bearer ' prefix
        
        // Simple token validation (you can make this more sophisticated)
        // For now, just check if token exists and has minimum length
        if (empty($token) || strlen($token) < 20) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
        
        // Add the token to request for potential use later
        $request->merge(['api_token' => $token]);
        
        return $next($request);
    }
}
