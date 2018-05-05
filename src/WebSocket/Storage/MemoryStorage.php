<?php

namespace Nuwave\Lighthouse\Subscriptions\WebSocket\Storage;

use Nuwave\Lighthouse\Subscriptions\Support\Contracts\SubscriberStorage;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Subscriber;

class MemoryStorage implements SubscriberStorage
{
    /**
     * Map of connections.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * Map of subscriptions
     *
     * @var array
     */
    protected $subscriptions = [];

    /**
     * Get connection by id.
     *
     * @param  int $connection_id
     *
     * @return array|null
     */
    public function find($connection_id)
    {
        return array_get($this->connections, $connection_id);
    }

    /**
     * Get all connections.
     *
     * @return array
     */
    public function all()
    {
        return $this->connections;
    }

    /**
     * Store a new connection.
     *
     * @param int $connection_id
     * @param array $init
     *
     * @return void
     */
    public function store($connection_id, array $init = [])
    {
        if (! isset($this->connections[$connection_id])) {
            $this->connections[$connection_id] = [
                'init' => $init,
                'subscriptions' => []
            ];
        }
    }

    /**
     * Get connection's initial data.
     *
     * @param  int $connection_id
     *
     * @return array
     */
    public function initialData($connection_id)
    {
        return array_get($this->connections, "{$connection_id}.init", []);
    }

    /**
     * Get list of subscribers for a subscription.
     *
     * @param  string $subscription
     *
     * @return array
     */
    public function get($subscription)
    {
        $connections = array_get($this->subscriptions, $subscription, []);

        return collect($connections)->filter(function ($connection_id) {
            return isset($this->connections, $connection_id);
        })->flatMap(function ($connection_id) {
            return $this->connections[$connection_id]['subscriptions'];
        })->filter(function ($subscriber) use ($subscription) {
            return $subscriber instanceof Subscriber
                && $subscriber->key() == $subscription;
        })->values()->toArray();
    }

    /**
     * Attach new subscription to connection.
     *
     * @param Subscriber $subscriber
     *
     * @return void
     */
    public function subscribe(Subscriber $subscriber)
    {
        $subscription = $subscriber->subscription();
        $connection_id = $subscriber->connectionId();
        $connection = array_get($this->connections, $connection_id);

        if (! $connection) {
            $connection = [];
            $this->store($connection_id);
        }

        $subscriber->setInitialPayload(array_get($connection, 'init', []));

        $connection['subscriptions'][] = $subscriber;
        $this->connections[$connection_id] = $connection;

        $this->subscriptions[$subscription] = array_unique(array_merge(
            $this->subscription($subscription),
            [$connection_id]
        ));
    }

    /**
     * Detach subscription from connection.
     *
     * @param  string $connection_id
     * @param  string $id
     *
     * @return void
     */
    public function unsubscribe($connection_id, $id)
    {
        $connection = array_get($this->connections, $connection_id);

        if (! $connection) {
            return;
        }

        $subscriber = collect($connection['subscriptions'])
            ->first(function (Subscriber $subscriber) use ($id) {
                return $subscriber->id() == $id;
            });

        if ($subscriber) {
            $key = $subscriber->subscription();

            $this->subscriptions[$key] = array_filter(
                $this->subscription($key),
                function ($id) use ($connection_id) {
                    return $id != $connection_id;
                }
            );
        }

        $this->connections[$connection_id]['subscriptions'] = collect($connection['subscriptions'])
            ->reject(function (Subscriber $subscriber) use ($id) {
                return $subscriber->id() == $id;
            })->toArray();
    }

    /**
     * Detach connection.
     *
     * @param  int $connection_id
     *
     * @return void
     */
    public function disconnect($connection_id)
    {
        if (isset($this->connections[$connection_id])) {
            collect($this->connections[$connection_id]['subscriptions'])
                ->each(function (Subscriber $subscriber) use ($connection_id) {
                    $this->unsubscribe($connection_id, $subscriber->id());
                });

            unset($this->connections[$connection_id]);
        }
    }

    /**
     * Get subscription by key.
     *
     * @param  string $key
     *
     * @return array
     */
    protected function subscription($key)
    {
        return array_get($this->subscriptions, $key, []);
    }
}
