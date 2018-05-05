<?php

namespace Nuwave\Lighthouse\Subscriptions\WebSocket;

use Nuwave\Lighthouse\Subscriptions\Support\Parser;
use Nuwave\Lighthouse\Subscriptions\Support\Exceptions\UnauthorizedSubscriptionRequest;
use Nuwave\Lighthouse\Subscriptions\Schema\Fields\SubscriptionField;
use Nuwave\Lighthouse\Subscriptions\WebSocket\ContextManager;

class Subscriber
{
    /**
     * Client assigned id.
     *
     * @var string
     */
    protected $id;

    /**
     * Initial payload.
     *
     * @var array
     */
    protected $init;

    /**
     * Subscription parameters
     *
     * @var array
     */
    protected $payload;

    /**
     * Websocket connection id.
     *
     * @var int
     */
    protected $connection_id;

    /**
     * Subscription context.
     *
     * @var mixed
     */
    protected $context;

    /**
     * Create new subscriber instance.
     *
     * @param string $id
     * @param array  $payload
     * @param int $connection_id
     * @param array $init
     */
    public function __construct($id, array $payload, $connection_id, $init = [])
    {
        $this->id = $id;
        $this->payload = $payload;
        $this->connection_id = $connection_id;
        $this->init = $init;
    }

    /**
     * Create instance from connection.
     *
     * @param  int $connection_id
     * @param  array $msg
     * @param  array $init
     *
     * @return self
     */
    public static function connection($connection_id, array $msg, array $init = [])
    {
        $instance = new static(
            array_get($msg, 'id'),
            array_get($msg, 'payload', []),
            $connection_id,
            $init
        );

        if (! $field = $instance->field()) {
            return $instance;
        }

        if (! $field->can($instance, $instance->context(), $instance->variables())) {
            throw new UnauthorizedSubscriptionRequest('unauthorized request');
        }

        return $instance;
    }

    /**
     * Set connection's initial payload.
     *
     * @param array $payload
     *
     * @return self
     */
    public function setInitialPayload($payload)
    {
        $this->init = $payload;

        return $this;
    }

    /**
     * Get the client id.
     *
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Generate subscription key.
     *
     * @return string
     */
    public function key()
    {
        return Parser::key($this->payload());
    }

    /**
     * Get connection's initial payload
     *
     * @return array
     */
    public function initialPayload()
    {
        return array_get($this->init, 'payload', []);
    }

    /**
     * Get message payload.
     *
     * @return array
     */
    public function payload()
    {
        return $this->payload;
    }

    /**
     * Get subscription name.
     *
     * @return string
     */
    public function subscription()
    {
        return Parser::key($this->payload);
    }

    /**
     * Get the websocket connection id.
     *
     * @return int
     */
    public function connectionId()
    {
        return $this->connection_id;
    }

    /**
     * Get input from payload.
     *
     * @param  string $key
     * @param  mixed $default
     *
     * @return mixed
     */
    public function input($key, $default = null)
    {
        return array_get($this->payload, $key, $default);
    }

    /**
     * Get json encoded item from payload.
     *
     * @param  string $key
     * @param  mixed $default
     *
     * @return string
     */
    public function json($key, $default = null)
    {
        return json_encode([$key => $this->input($key, $default)]);
    }

    /**
     * Get subscription query.
     *
     * @return string
     */
    public function query()
    {
        return $this->input('query');
    }

    /**
     * Get subscription variables.
     *
     * @return array
     */
    public function variables()
    {
        return $this->input('variables', []);
    }

    /**
     * Get subscription operation name.
     *
     * @return string
     */
    public function operationName()
    {
        return $this->input('operationName', '');
    }

    /**
     * Get instance of context manager.
     *
     * @return mixed
     */
    public function context()
    {
        if (! $this->context) {
            $context = app(ContextManager::class)->build();
            $context->setSubscriber($this);
            $context->build();
            $this->context = $context;
        }

        return $this->context;
    }

    /**
     * Resolve subscription.
     *
     * @param  mixed $root
     *
     * @return array
     */
    public function resolve($root = null)
    {
        return app('graphql')->execute(
            $this->query(),
            $this->context(),
            $this->variables(),
            $root
        );
    }

    /**
     * Get subscription field instance.
     *
     * @return SubscriptionField
     */
    protected function field()
    {
        $handle = Parser::handle($this->subscription());

        return app()->has($handle) ? app($handle) : null;
    }
}
