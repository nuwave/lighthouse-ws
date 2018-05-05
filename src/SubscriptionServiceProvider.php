<?php

namespace Nuwave\Lighthouse\Subscriptions;

use Illuminate\Support\ServiceProvider;
use Illuminate\Broadcasting\BroadcastManager;
use Nuwave\Lighthouse\Subscriptions\WebSocket\TransportManager;
use Nuwave\Lighthouse\Subscriptions\WebSocket\ContextManager;
use Nuwave\Lighthouse\Subscriptions\WebSocket\SubscriptionManager;
use Nuwave\Lighthouse\Subscriptions\Schema\Directives\WebsocketDirective;
use Nuwave\Lighthouse\Subscriptions\Schema\Directives\SubscriptionDirective;
use Nuwave\Lighthouse\Subscriptions\Support\Broadcasters\RedisBroadcaster;
use Laravel\Passport\Guards\TokenGuard;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\TokenRepository;
use League\OAuth2\Server\ResourceServer;

class SubscriptionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param BroadcastManager $broadcastManager
     *
     * @return void
     */
    public function boot(BroadcastManager $broadcastManager)
    {
        $this->publishes([
            __DIR__ . '/../config/config.php' => config_path('lighthouse_subscriptions.php')
        ]);

        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'lighthouse_subscriptions');

        $broadcastManager->extend('lighthouse', function ($app, array $config) {
            return new RedisBroadcaster($app->make('redis'), $config['connection'] ?? null);
        });

        $this->registerDirectives();
        $this->registerSubscriptions();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('lighthouse.server', function ($app) {
            return new Server(
                config('lighthouse_subscriptions.port'),
                config('lighthouse_subscriptions.keep_alive')
            );
        });

        $this->app->singleton(TransportManager::class);
        $this->app->singleton(SubscriptionManager::class, function ($app) {
            return new SubscriptionManager($app);
        });
        $this->app->singleton(ContextManager::class, function ($app) {
            return new ContextManager($app);
        });

        $this->app->alias(TransportManager::class, 'graphql.ws-transport');

        $this->app->when(\Nuwave\Lighthouse\Subscriptions\WebSocket\Context\PassportContext::class)
            ->needs(TokenGuard::class)
            ->give(function ($app) {
                $auth = $app['auth'];
                $guard =  $app['config']['lighthouse.auth_guard'] ?: $auth->getDefaultDriver();
                $config = $this->app['config']["auth.guards.{$guard}"];
                $provider = $auth->createUserProvider($config['provider']);

                return new TokenGuard(
                    $app->make(ResourceServer::class),
                    $provider,
                    $app->make(TokenRepository::class),
                    $app->make(ClientRepository::class),
                    $app->make('encrypter')
                );
            });

        $this->commands([Support\Console\Commands\WebSocketServerCommand::class]);
    }

    /**
     * Register directives w/ Lighthouse.
     *
     * @return void
     */
    protected function registerDirectives()
    {
        graphql()->directives()->register(SubscriptionDirective::class);
        graphql()->directives()->register(WebsocketDirective::class);
    }

    /**
     * Register subscriptions.
     *
     * @return void
     */
    protected function registerSubscriptions()
    {
        graphql()->prepSchema();

        if ($type = schema()->instance('Subscription')) {
            $type->getFields();
        }
    }
}
