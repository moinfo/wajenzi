<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('client')->check()) {
            return redirect()->route('client.login');
        }

        if (!Auth::guard('client')->user()->portal_access_enabled) {
            Auth::guard('client')->logout();
            return redirect()->route('client.login')
                ->withErrors(['email' => 'Your portal access has been disabled. Please contact your project manager.']);
        }

        return $next($request);
    }
}
