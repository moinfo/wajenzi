<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class HandleRememberTokenError
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'unserialize(): Error at offset') !== false) {
                // Clear the remember token cookie
                return redirect()->route('login')
                    ->withCookie(Cookie::forget('remember_web_' . sha1(config('app.name') . '_web')))
                    ->with('error', 'Your session has expired. Please login again.');
            }
            
            throw $e;
        }
    }
}