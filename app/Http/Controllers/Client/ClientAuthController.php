<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('client.auth.login', ['page_title' => 'Client Portal Login']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required',
        ]);

        $login = $request->input('login');
        $remember = $request->boolean('remember');

        // Determine if input is email or phone number
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
        $credentials = [$field => $login, 'password' => $request->input('password')];

        if (Auth::guard('client')->attempt($credentials, $remember)) {
            $request->session()->regenerate();

            Auth::guard('client')->user()->update(['last_login_at' => now()]);

            return redirect()->intended(route('client.dashboard'));
        }

        return back()->withErrors([
            'login' => 'The provided credentials do not match our records.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::guard('client')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('client.login');
    }
}
