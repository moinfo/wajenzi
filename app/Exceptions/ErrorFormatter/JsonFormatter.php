<?php

namespace App\Exceptions\ErrorFormatter;

use App\Http\Middleware\Formatters\FormatterInterface;
use Exception;

class JsonFormatter implements FormatterInterface
{
    public function format(Exception $exception): string
    {
        $data = [
            'error' => [
                'type' => get_class($exception),
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    public function isAcceptable(string $accept): bool
    {
        return strpos($accept, 'application/json') !== false;
    }

    public function getHeaders(): array
    {
        return ['Content-Type' => 'application/json; charset=utf-8'];
    }
}
