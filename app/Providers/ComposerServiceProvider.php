<?php


namespace App\Providers;

use App\Http\View\Composers\AdminComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Register view composers only after the view service is available
        $this->app->booted(function () {
            if ($this->app->bound('view')) {
                View::composer(
                    ['*'], AdminComposer::class
                );
            }
        });
    }
}
