<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        if ($user->token_expires_at && Carbon::parse($user->token_expires_at)->isPast()) {
            $user->update(['api_token' => null, 'token_expires_at' => null]);
            return response()->json(['message' => 'Token expired'], 401);
        }

        auth()->login($user);
        return $next($request);
    }
}
