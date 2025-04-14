<?php

namespace SwellSystems\Conversa;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use VendorName\Conversa\Http\Middleware\RateLimitMessages;

class ConversaServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/conversa.php', 'conversa'
        );

        // Register middleware
        $this->app['router']->aliasMiddleware('conversa.rate-limit', RateLimitMessages::class);

        // Register services
        $this->app->singleton(MessageEncryption::class);
        $this->app->singleton(UserPresenceManager::class);
        $this->app->singleton(MessageSearch::class);
        $this->app->singleton(MessageThread::class);

        // Register WebSocket server
        $this->app->singleton(WebSocket\WebSocketServer::class, function ($app) {
            return new WebSocket\WebSocketServer(
                $app->make(Services\UserPresenceManager::class),
                $app->make(Services\MessageEncryption::class)
            );
        });

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\WebSocketServer::class,
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerViews();
        $this->registerMigrations();
        $this->registerBroadcasting();
        $this->registerEvents();
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__.'/../config/conversa.php' => config_path('conversa.php'),
            ], 'conversa-config');

            // Views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/conversa'),
            ], 'conversa-views');

            // Migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'conversa-migrations');

            // Assets
            $this->publishes([
                __DIR__.'/../resources/js' => resource_path('js/vendor/conversa'),
            ], 'conversa-assets');

            // Scout configuration
            $this->publishes([
                __DIR__.'/../config/scout.php' => config_path('scout.php'),
            ], 'conversa-scout');
        }
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/conversa.php');
        });
    }

    /**
     * Get the route group configuration array.
     *
     * @return array<string, mixed>
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('conversa.routes.prefix', 'conversa'),
            'middleware' => config('conversa.routes.middleware', ['web', 'auth']),
            'namespace' => 'VendorName\\Conversa\\Http\\Controllers',
        ];
    }

    /**
     * Register the package views.
     */
    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'conversa');

        // Share common data with all views
        View::composer('conversa::*', function ($view) {
            $view->with('conversaConfig', config('conversa'));
        });
    }

    /**
     * Register the package migrations.
     */
    protected function registerMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register the package's broadcasting channels.
     */
    protected function registerBroadcasting(): void
    {
        require __DIR__.'/Broadcasting/channels.php';

        // Register Echo configuration
        $this->app['config']->set('broadcasting.default', 'pusher');
        
        if (! $this->app['config']->get('broadcasting.connections.pusher')) {
            $this->app['config']->set('broadcasting.connections.pusher', [
                'driver' => 'pusher',
                'key' => env('PUSHER_APP_KEY'),
                'secret' => env('PUSHER_APP_SECRET'),
                'app_id' => env('PUSHER_APP_ID'),
                'options' => [
                    'cluster' => env('PUSHER_APP_CLUSTER'),
                    'encrypted' => true,
                ],
            ]);
        }
    }

    /**
     * Register the package's event listeners.
     */
    protected function registerEvents(): void
    {
        // Register event listeners and subscribers
        Event::listen('eloquent.created: VendorName\\Conversa\\Models\\ConversaMessage', function ($message) {
            event(new Events\MessageSent($message));
        });

        Event::listen('eloquent.deleted: VendorName\\Conversa\\Models\\ConversaMessage', function ($message) {
            event(new Events\MessageDeleted($message));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            'conversa',
        ];
    }
}
