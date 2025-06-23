<?php

// Emergency route to clear corrupted cookies
Route::get('/clear-cookies', function () {
    $cookieName = 'remember_web_' . sha1(config('app.name') . '_web');
    
    return redirect('/login')
        ->withCookie(cookie()->forget($cookieName))
        ->with('message', 'Cookies cleared. Please try logging in again.');
})->name('clear.cookies');