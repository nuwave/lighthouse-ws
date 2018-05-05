<?php

namespace Nuwave\Lighthouse\Subscriptions\WebSocket;

use Clue\React\Redis\Client;
use Clue\React\Redis\Factory;
use Nuwave\Lighthouse\Subscriptions\Support\Log;
use Nuwave\Lighthouse\Subscriptions\Support\Protocol;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class Pusher implements WampServerInterface
{
    /**
     * Create instance of redis pusher.
     *
     * @param \React\EventLoop\StreamSelectLoop $loop
     */
    public function __construct($loop)
    {
        $redis_host = config('database.redis.default.host');
        $redis_port = config('database.redis.default.port');
        $uri = sprintf('redis://%s:%s', $redis_host, $redis_port);

        $factory = new Factory($loop);
        $factory->createClient($uri)->then(function (Client $client) use ($loop) {
            $client->subscribe(Protocol::BROADCAST_CHANNEL);
            $client->subscribe(Protocol::WEBSOCKET_CHANNEL);
            $client->on('message', function ($channel, $payload) use ($loop) {
                Log::v(' ', $loop, 'Incoming Message ['.$channel.']:');
                Log::v(' ', $loop, $payload);

                if ($channel === Protocol::BROADCAST_CHANNEL) {
                    $payload = json_decode($payload, true);
                } elseif ($channel === Protocol::WEBSOCKET_CHANNEL) {
                    $payload = unserialize($payload);
                }

                if (array_get($payload, 'type') == 'keepalive') {
                    app('graphql.ws-transport')->handleKeepAlive();
                } else {
                    app('graphql.ws-transport')->broadcast($payload);
                }
            });

            Log::v(' ', $loop, "Connected to Redis.");
        }, function ($e) {
            info('Unable to establish redis connection', ['error' => $e]);
        });
    }

    /**
     * Create a new instance.
     *
     * @param  \React\EventLoop\StreamSelectLoop $loop
     *
     * @return self
     */
    public static function run($loop)
    {
        return new static($loop);
    }

    public function onOpen(ConnectionInterface $conn)
    {
    }

    public function onClose(ConnectionInterface $conn)
    {
    }

    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
    }

    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
    }

    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
    }
}
