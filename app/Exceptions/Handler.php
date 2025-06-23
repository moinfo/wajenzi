<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        // You can add custom reporting logic here if needed
        $this->reportable(function (Throwable $e) {
            // Custom reporting logic
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $exception)
    {
        // Handle JSON requests
        if ($request->expectsJson()) {
            return response()->json([
                'error' => $exception->getMessage(),
                'code' => $this->getStatusCode($exception)
            ], $this->getStatusCode($exception));
        }

        // Handle "Undefined variable" errors
        if ($exception instanceof \ErrorException &&
            strpos($exception->getMessage(), 'Undefined variable') !== false) {

            return response()->view('errors.general', [
                'exception' => $exception,
                'title' => 'Variable Error',
                'message' => 'There was an error in the application: ' . $exception->getMessage()
            ], 500);
        }

        // Handle "array offset on value of type int" errors
        if ($exception instanceof \ErrorException &&
            strpos($exception->getMessage(), 'array offset on value of type int') !== false) {

            return response()->view('errors.general', [
                'exception' => $exception,
                'title' => 'Data Error',
                'message' => 'We encountered an issue processing the data.'
            ], 500);
        }

        // Add more specific error type handling here
        // Handle other PHP errors
        if ($exception instanceof \ErrorException) {
            return response()->view('errors.general', [
                'exception' => $exception,
                'title' => 'PHP Error',
                'message' => $exception->getMessage()
            ], 500);
        }

        // Default to Laravel's handling for other exceptions
        return parent::render($request, $exception);
    }

    /**
     * Get the HTTP status code for an exception.
     *
     * @param  \Throwable  $exception
     * @return int
     */
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
            'Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException' => 405,
            'Illuminate\Validation\ValidationException' => 422,
            // Add more mappings as needed
        ];

        $exceptionClass = get_class($exception);

        return $statusCodes[$exceptionClass] ?? 500;
    }
}
