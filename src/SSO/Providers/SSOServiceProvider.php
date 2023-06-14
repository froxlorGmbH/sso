<?php

namespace FroxlorGmbH\SSO\Providers;

use FroxlorGmbH\SSO\Auth\TokenGuard;
use FroxlorGmbH\SSO\Auth\UserProvider;
use FroxlorGmbH\SSO\SSO;
use FroxlorGmbH\SSO\Helpers\JwtParser;
use FroxlorGmbH\SSO\Contracts;
use FroxlorGmbH\SSO\Repository;
use Bitinflow\Payments\BitinflowPayments;
use Illuminate\Auth\RequestGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class SSOServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            dirname(__DIR__, 3) . '/config/sso.php' => config_path('sso.php'),
        ], 'config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(dirname(__DIR__, 3) . '/config/sso.php', 'sso');
        $this->app->singleton(Contracts\AppTokenRepository::class, Repository\AppTokenRepository::class);
        $this->app->singleton(SSO::class, function () {
            return new SSO;
        });
        $this->app->singleton(BitinflowPayments::class, function () {
            return new BitinflowPayments;
        });

        $this->registerGuard();
    }

    /**
     * Register the token guard.
     *
     * @return void
     */
    protected function registerGuard()
    {
        Auth::resolved(function ($auth) {
            $auth->extend('sso', function ($app, $name, array $config) {
                return tap($this->makeGuard($config), function ($guard) {
                    $this->app->refresh('request', $guard, 'setRequest');
                });
            });
        });
    }

    /**
     * Make an instance of the token guard.
     *
     * @param array $config
     * @return RequestGuard
     */
    protected function makeGuard(array $config): RequestGuard
    {
        return new RequestGuard(function ($request) use ($config) {
            return (new TokenGuard(
                new UserProvider(Auth::createUserProvider($config['provider']), $config['provider']),
                $this->app->make('encrypter'),
                $this->app->make(JwtParser::class)
            ))->user($request);
        }, $this->app['request']);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            SSO::class,
            BitinflowPayments::class,
        ];
    }
}
