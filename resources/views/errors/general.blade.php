<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Error' }} - Wajenzi</title>
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
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-icon {
            font-size: 60px;
            color: #e74c3c;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 26px;
            color: #e74c3c;
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
            padding: 10px 20px;
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
        .details {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: left;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="error-icon">⚠️</div>
    <h1 class="error-title">{{ $title ?? 'Something went wrong' }}</h1>

    <div class="error-message">
        @if(app()->environment('production'))
            We've encountered an issue while processing your request. Our team has been notified.
        @else
            {{ $message ?? $exception->getMessage() }}
        @endif
    </div>

    <div class="actions">
        <a href="{{ url('/') }}" class="btn">Return to Home</a>
        <a href="javascript:history.back()" class="btn">Go Back</a>
    </div>
    @if(app()->environment('development'))
        <div class="details">
            <p><strong>Error Details (visible in development only):</strong></p>
            <p>{{ $exception->getMessage() }}</p>
            <p><strong>File:</strong> {{ $exception->getFile() }}:{{ $exception->getLine() }}</p>
        </div>
    @endif
</div>
</body>
</html>
