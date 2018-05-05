<?php

namespace Nuwave\Lighthouse\Subscriptions\Schema\Fields;

use Nuwave\Lighthouse\Subscriptions\Support\Parser;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Subscriber;

abstract class SubscriptionField
{
    /**
     * Generate a key for this field.
     *
     * @param array $params
     * @param string|null $subscription
     *
     * @return string
     */
    public function key(array $params, $subscription = null)
    {
        return Parser::key($params, $subscription);
    }

    /**
     * Check if subscription can be created.
     *
     * @param Subscriber $subscriber
     * @param mixed $context
     * @param array $args
     *
     * @return bool
     */
    public function can(Subscriber $subscriber, $context, array $args = [])
    {
        return true;
    }

    /**
     * Get subscription variables to filter by.
     *
     * @param Subscriber $subscriber
     * @param mixed $event
     *
     * @return bool
     */
    public function filter(Subscriber $subscriber, $event)
    {
        return true;
    }

    /**
     * Transform event data.
     *
     * @param  mixed $event
     *
     * @return mixed
     */
    public function transform($event)
    {
        return $event;
    }

    /**
     * Resolve the subscription.
     *
     * @param mixed $root
     * @param array $args
     * @param mixed $context
     *
     * @return mixed
     */
    abstract public function resolve($root, array $args, $context);
}
