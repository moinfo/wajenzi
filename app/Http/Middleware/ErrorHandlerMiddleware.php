<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class ErrorHandlerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Convert PHP errors to exceptions
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $response = $next($request);
            // Restore the original error handler
            restore_error_handler();
            return $response;
        } catch (\Throwable $e) {
            // Restore the original error handler
            restore_error_handler();

            // Handle exception
            return $this->renderException($request, $e);
        }
    }

    protected function renderException(Request $request, \Throwable $e)
    {
        // Your exception handling logic
        if ($request->expectsJson()) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->view('errors.general', ['exception' => $e], 500);
    }
    protected function getStatusCode(Throwable $exception): int
    {
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }

        if (property_exists($exception, 'status')) {
            return $exception->status;
        }

        // Map exception types to status codes
        $statusCodes = [
            'Illuminate\Auth\AuthenticationException' => 401,
            'Illuminate\Auth\Access\AuthorizationException' => 403,
            'Illuminate\Database\Eloquent\ModelNotFoundException' => 404,
            'Symfony\Component\HttpKernel\Exception\NotFoundHttpException' => 404,
            // Add more mappings as needed
        ];

        $exceptionClass = get_class($exception);

        return $statusCodes[$exceptionClass] ?? 500;
    }
}
