<?php

namespace Nuwave\Lighthouse\Subscriptions\Support\Contracts;

use Nuwave\Lighthouse\Subscriptions\WebSocket\Subscriber;

interface SubscriberStorage
{
    /**
     * Get all connections.
     *
     * @return array
     */
    public function all();

    /**
     * Get subscribers for subscription.
     *
     * @param string $subscription
     *
     * @return array
     */
    public function get($subscription);

    /**
     * Get subscriber by connection id.
     *
     * @param  int $connection_id
     *
     * @return array|null
     */
    public function find($connection_id);

    /**
     * Get connection's initial data.
     *
     * @param  int $connection_id
     *
     * @return array
     */
    public function initialData($connection_id);

    /**
     * Store a new connection.
     *
     * @param  int $connection_id
     * @param  array $init
     *
     * @return void
     */
    public function store($connection_id, array $init = []);

    /**
     * Attach new subscription to connection.
     *
     * @param Subscriber $subscriber
     *
     * @return void
     */
    public function subscribe(Subscriber $subscriber);

    /**
     * Detach subscription from connection.
     *
     * @param  string $connection_id
     * @param  string $id
     *
     * @return void
     */
    public function unsubscribe($connection_id, $id);

    /**
     * Detach connection.
     *
     * @param  int $connection_id
     *
     * @return void
     */
    public function disconnect($connection_id);
}
