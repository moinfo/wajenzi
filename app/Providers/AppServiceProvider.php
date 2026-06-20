<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
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
        Paginator::useBootstrapFour();

        // Override the ringlesoft <x-ringlesoft-approval-actions> component so the
        // approvals table renders each approver's comment as visible text.
        Blade::component('approval-actions', \App\View\Components\ApprovalActions::class, 'ringlesoft');
    }
}
