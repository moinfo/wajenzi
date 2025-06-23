<?php

namespace App\Http\Middleware\Formatters;

use Exception;

interface FormatterInterface
{
    /**
     * Format the exception.
     */
    public function format(Exception $exception): string;

    /**
     * Check if the formatter is acceptable for the request.
     */
    public function isAcceptable(string $accept): bool;

    /**
     * Get response headers specific to this formatter.
     */
    public function getHeaders(): array;
}
