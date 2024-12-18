<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // if (!$this->app->bound('blade.compiler')) {
        //     $this->app->singleton('blade.compiler', function ($app) {
        //         return $app['view']->getEngineResolver()->resolve('blade')->getCompiler();
        //     });
        // }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
