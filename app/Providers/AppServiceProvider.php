<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // FIX DEVOPS: Força HTTPS para evitar Loop do Traefik
        if (str_contains(config('app.url'), 'https')) {
            URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', 'on');
        }

        // (Mantenha outros códigos originais do Krayin aqui, se houver, como Schema::defaultStringLength)
        // Schema::defaultStringLength(191); // Exemplo comum no Krayin
    }
}
