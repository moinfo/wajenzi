<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

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

        // Share sidebar data with all client views
        $clientUser = Auth::guard('client')->user();
        $sidebarProjects = Project::where('client_id', $clientUser->id)
            ->orderBy('project_name')
            ->select('id', 'project_name', 'status')
            ->get();

        View::share('sidebarProjects', $sidebarProjects);

        return $next($request);
    }
}
