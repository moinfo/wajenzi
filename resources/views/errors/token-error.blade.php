<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Expired - Wajenzi</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-icon {
            font-size: 60px;
            color: #f39c12;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 26px;
            color: #f39c12;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 18px;
            margin-bottom: 30px;
        }
        .actions {
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            margin: 0 10px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #2980b9;
        }
        .btn-primary {
            background-color: #27ae60;
        }
        .btn-primary:hover {
            background-color: #229954;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="error-icon">🔐</div>
    <h1 class="error-title">Session Expired</h1>

    <div class="error-message">
        {{ $message ?? 'Your session has expired. Please login again.' }}
    </div>

    <div class="actions">
        <a href="{{ route('login') }}" class="btn btn-primary">Login</a>
        <a href="{{ url('/') }}" class="btn">Return to Home</a>
    </div>
</div>

<script>
// Auto-redirect to login after 3 seconds
setTimeout(function() {
    window.location.href = '{{ route("login") }}';
}, 3000);
</script>
</body>
</html>