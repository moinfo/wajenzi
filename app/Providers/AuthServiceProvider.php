<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\SessionGuard;

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

        // Override the session guard to handle corrupted remember tokens
        Auth::extend('session', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider'] ?? null);

            $guard = new class(
                $name,
                $provider,
                $app['session.store'],
                $app['request']
            ) extends SessionGuard {
                /**
                 * Get the decrypted recaller cookie for the request.
                 *
                 * @return \Illuminate\Auth\Recaller|null
                 */
                protected function recaller()
                {
                    try {
                        return parent::recaller();
                    } catch (\Exception $e) {
                        // If unserialize fails, clear the cookie and return null
                        if (strpos($e->getMessage(), 'unserialize') !== false) {
                            $this->getCookieJar()->queue($this->getCookieJar()->forget($this->getRecallerName()));
                            return null;
                        }
                        throw $e;
                    }
                }
            };

            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));

            return $guard;
        });
    }
}
