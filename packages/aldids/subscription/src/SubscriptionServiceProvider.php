<?php

namespace Aldids\Subscription;

use Illuminate\Support\ServiceProvider;


class SubscriptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //Register generate command
        $this->commands([
           \Aldids\Subscription\Console\SubscriptionInstall::class,
        ]);
 
        //Register Config file
        $this->mergeConfigFrom(__DIR__.'/../config/subscription.php', 'subscription');
 
        //Publish Config
        $this->publishes([
           __DIR__.'/../config/subscription.php' => config_path('subscription.php'),
        ], 'subscription-config');
 
        //Register Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
 
        //Publish Migrations
        $this->publishes([
           __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'subscription-migrations');
        //Register views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'subscription');
 
        //Publish Views
        $this->publishes([
           __DIR__.'/../resources/views' => resource_path('views/vendor/subscription'),
        ], 'subscription-views');
 
        //Register Langs
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'subscription');
 
        //Publish Lang
        $this->publishes([
           __DIR__.'/../resources/lang' => base_path('lang/vendor/subscription'),
        ], 'subscription-lang');
 
        //Register Routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
 
    }

    public function boot(): void
    {
        //you boot methods here
    }
}
