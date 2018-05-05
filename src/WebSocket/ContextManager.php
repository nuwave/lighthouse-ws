<?php

namespace Nuwave\Lighthouse\Subscriptions\WebSocket;

use Closure;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Context\PassportContext;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Context\SubscriberContext;

class ContextManager
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Resolved connection storage drivers.
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * Registered custom storage creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new manager instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Build new context instance.
     *
     * @return \Nuwave\Lighthouse\Subscriptions\Support\Contracts\SubscriptionContext
     */
    public function build()
    {
        return $this->driver();
    }

    /**
     * Get a driver instance.
     *
     * @param  string|null  $name
     *
     * @return \Nuwave\Lighthouse\Subscriptions\Support\Contracts\SubscriptionContext
     */
    public function driver($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }

    /**
     * Attempt to get the connection from the local cache.
     *
     * @param string $name
     *
     * @return \Nuwave\Lighthouse\Subscriptions\Support\Contracts\SubscriptionContext
     */
    protected function get($name)
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve the given store.
     *
     * @param  string  $driver
     *
     * @return \Nuwave\Lighthouse\Subscriptions\Support\Contracts\SubscriptionContext
     */
    protected function resolve($driver)
    {
        if (is_null($driver)) {
            throw new \InvalidArgumentException("Subscription Storage [{$driver}] is not defined.");
        }

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        $driverMethod = 'create'.ucfirst($driver).'Driver';

        if (! method_exists($this, $driverMethod)) {
            throw new \InvalidArgumentException("Subscription storage driver [{$driver}] is not supported.");
        }

        return $this->{$driverMethod}();
    }

    /**
     * Call a custom driver creator.
     *
     * @param string $driver
     *
     * @return \Nuwave\Lighthouse\Subscriptions\Support\Contracts\SubscriptionContext
     */
    protected function callCustomCreator($driver)
    {
        return $this->customCreators[$driver]($this->app);
    }

    /**
     * Create instance of subscriber context.
     *
     * @return SubscriberContext
     */
    public function createSubscriberDriver()
    {
        return app(SubscriberContext::class);
    }

    /**
     * Create instance of passport context.
     *
     * @return PassportContext
     */
    public function createPassportDriver()
    {
        return app(PassportContext::class);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['lighthouse_subscriptions.context'];
    }

    /**
     * Set the default driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['lighthouse_subscriptions.context'] = $name;
    }

    /**
     * Register a custom storage driver closure.
     *
     * @param  string  $driver
     * @param  Closure $callback
     *
     * @return self
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }
}
