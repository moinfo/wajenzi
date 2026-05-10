<?php

namespace App\Providers;

use App\Auth\SafeSessionGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Use a named SessionGuard subclass so that the session login key
        // (login_<guard>_<sha1(static::class)>) is stable across PHP-FPM
        // workers. Anonymous classes break this because PHP appends a
        // worker-local counter to the generated class name, so one worker
        // would authenticate the user under one key and a different worker
        // would look for the user under a different key on the next request.
        Auth::extend('session', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);

            $guard = new SafeSessionGuard(
                $name,
                $provider,
                $app['session.store'],
                $app['request']
            );

            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    }
}
