<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Http\Middleware\ErrorHandlerMiddleware;
use App\Http\Middleware\Formatters\HtmlFormatter;
use App\Http\Middleware\Formatters\JsonFormatter;
use App\Http\Middleware\Formatters\XmlFormatter;
// Add other formatters as needed

class ErrorHandlerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ErrorHandlerMiddleware::class, function ($app) {
            return new ErrorHandlerMiddleware([
                new HtmlFormatter(),
                new JsonFormatter(),
                // Add other formatters
            ]);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
