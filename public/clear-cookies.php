<?php
/**
 * Emergency cookie clearing script
 * Access this directly at: domain.com/clear-cookies.php
 */

// Clear all cookies for this domain
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        // Clear each cookie
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
    }
}

// Specifically target Laravel session and remember cookies
$app_name = 'wajenzi'; // Change this to your actual app name from .env
$session_cookie = 'wajenzi_session';
$remember_cookie = 'remember_web_' . sha1($app_name . '_web');

setcookie($session_cookie, '', time() - 3600, '/');
setcookie($remember_cookie, '', time() - 3600, '/');
setcookie('laravel_session', '', time() - 3600, '/');

// Clear any other potential Laravel cookies
setcookie('XSRF-TOKEN', '', time() - 3600, '/');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cookies Cleared</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; text-align: center; }
        .container { max-width: 500px; margin: 0 auto; }
        .success { color: #22c55e; font-size: 18px; }
        .btn { background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cookies Cleared Successfully</h1>
        <p class="success">✓ All cookies have been cleared from your browser.</p>
        <p>You can now try logging in again.</p>
        <a href="/login" class="btn">Go to Login</a>
        
        <hr style="margin: 40px 0;">
        
        <h3>If you still have issues:</h3>
        <ol style="text-align: left;">
            <li>Clear your browser cache and cookies manually</li>
            <li>Try using incognito/private browsing mode</li>
            <li>Try a different browser</li>
            <li>Contact support if the problem persists</li>
        </ol>
    </div>
</body>
</html>