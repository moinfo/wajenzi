<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wajenzi Pro - System Overview</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            line-height: 1.7;
            color: #1a1a2e;
            background: #f8f9fa;
        }
        .header {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: white;
            padding: 40px 0;
            text-align: center;
        }
        .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 8px; }
        .header p { font-size: 1rem; opacity: 0.85; }
        .container {
            max-width: 960px;
            margin: 0 auto;
            padding: 40px 24px 60px;
        }
        .content h1 { font-size: 1.8rem; font-weight: 700; margin: 32px 0 16px; color: #1a1a2e; border-bottom: 2px solid #e9ecef; padding-bottom: 8px; }
        .content h2 { font-size: 1.5rem; font-weight: 600; margin: 28px 0 12px; color: #16213e; }
        .content h3 { font-size: 1.2rem; font-weight: 600; margin: 24px 0 10px; color: #0f3460; }
        .content h4 { font-size: 1.05rem; font-weight: 600; margin: 20px 0 8px; color: #333; }
        .content p { margin: 10px 0; color: #333; }
        .content ul, .content ol { margin: 10px 0 10px 24px; color: #333; }
        .content li { margin: 4px 0; }
        .content strong { color: #1a1a2e; }
        .content code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #d63384;
        }
        .content pre {
            background: #1a1a2e;
            color: #e9ecef;
            padding: 16px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 12px 0;
            font-size: 0.85em;
            line-height: 1.5;
        }
        .content pre code { background: none; color: inherit; padding: 0; }
        .content table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 0.92em;
        }
        .content table th {
            background: #1a1a2e;
            color: white;
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
        }
        .content table td {
            padding: 9px 12px;
            border-bottom: 1px solid #e9ecef;
        }
        .content table tr:nth-child(even) td { background: #f8f9fa; }
        .content table tr:hover td { background: #e9ecef; }
        .content hr { border: none; border-top: 2px solid #e9ecef; margin: 32px 0; }
        .content blockquote {
            border-left: 4px solid #0f3460;
            padding: 12px 16px;
            margin: 12px 0;
            background: #e8f4f8;
            border-radius: 0 6px 6px 0;
        }
        .footer {
            text-align: center;
            padding: 24px;
            color: #888;
            font-size: 0.85em;
            border-top: 1px solid #e9ecef;
        }
        @media (max-width: 640px) {
            .header { padding: 24px 16px; }
            .header h1 { font-size: 1.5rem; }
            .container { padding: 24px 16px 40px; }
            .content table { font-size: 0.82em; }
            .content table th, .content table td { padding: 6px 8px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Wajenzi Pro</h1>
        <p>System Module Documentation</p>
    </div>
    <div class="container">
        <div class="content">
            {!! $content !!}
        </div>
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} Wajenzi Professional Ltd. All rights reserved.
    </div>
</body>
</html>
