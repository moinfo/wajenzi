<?php

namespace App\Exceptions\ErrorFormatter;

use Exception;

class HtmlFormatter implements FormatterInterface
{
    public function format(Exception $exception): string
    {
        $title = get_class($exception);
        $message = $exception->getMessage();

        return "<!DOCTYPE html>
                <html>
                <head>
                    <meta charset=\"UTF-8\">
                    <title>Error: {$title}</title>
                    <style>
                        body { font-family: sans-serif; padding: 20px; }
                        .error { background: #f8d7da; padding: 15px; border-radius: 5px; }
                    </style>
                </head>
                <body>
                    <div class=\"error\">
                        <h1>{$title}</h1>
                        <p>{$message}</p>
                    </div>
                </body>
                </html>";
    }

    public function isAcceptable(string $accept): bool
    {
        return strpos($accept, 'text/html') !== false ||
            strpos($accept, '*/*') !== false;
    }

    public function getHeaders(): array
    {
        return ['Content-Type' => 'text/html; charset=utf-8'];
    }
}
