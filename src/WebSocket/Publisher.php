<?php

namespace Nuwave\Lighthouse\Subscriptions\WebSocket;

use Nuwave\Lighthouse\Subscriptions\Support\Protocol;

class Publisher
{
    /**
     * Broadcast event to clients.
     *
     * @param string $subscription
     * @param mixed $event
     *
     * @return void
     */
    public static function broadcast($subscription, $event)
    {
        app('redis')->publish(
            Protocol::WEBSOCKET_CHANNEL,
            serialize(compact('subscription', 'event'))
        );
    }

    /**
     * Send keep alive message to clients.
     *
     * @return void
     */
    public static function keepAlive()
    {
        app('redis')->publish(
            Protocol::BROADCAST_CHANNEL,
            json_encode(['type' => 'keepalive'])
        );
    }
}
