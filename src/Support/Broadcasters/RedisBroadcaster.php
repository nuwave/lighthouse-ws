<?php

namespace Nuwave\Lighthouse\Subscriptions\Support\Broadcasters;

use Illuminate\Broadcasting\Broadcasters\RedisBroadcaster as Broadcaster;
use Nuwave\Lighthouse\Subscriptions\Support\Protocol;

class RedisBroadcaster extends Broadcaster
{
    /**
     * {@inheritDoc}
     */
    public function auth($request)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function validAuthenticationResponse($request, $result)
    {
        //
    }

    /**
     * {@inheritDoc}
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $connection = $this->redis->connection($this->connection);

        foreach ($this->formatChannels($channels) as $subscription) {
            $connection->publish(Protocol::BROADCAST_CHANNEL, json_encode(array_merge(
                $payload,
                compact('subscription')
            )));
        }
    }
}
