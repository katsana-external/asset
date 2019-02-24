<?php

namespace Orchestra\Asset;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Contracts\Support\RegistrableProvider;

class AssetServiceProvider extends ServiceProvider implements DeferrableProvider, RegistrableProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerResolver();

        $this->registerDispatcher();

        $this->registerAsset();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerAsset(): void
    {
        $this->app->singleton('orchestra.asset', function (Application $app) {
            return new Factory($app->make('orchestra.asset.dispatcher'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerDispatcher(): void
    {
        $this->app->singleton('orchestra.asset.dispatcher', function (Application $app) {
            return new Dispatcher(
                $app->make('files'),
                $app->make('html'),
                $app->make('orchestra.asset.resolver'),
                $app->publicPath()
            );
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerResolver(): void
    {
        $this->app->singleton('orchestra.asset.resolver', function () {
            return new DependencyResolver();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['orchestra.asset', 'orchestra.asset.dispatcher', 'orchestra.asset.resolver'];
    }
}
