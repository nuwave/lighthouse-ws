<?php

namespace Nuwave\Lighthouse\Subscriptions\WebSocket;

use Exception;
use Nuwave\Lighthouse\Subscriptions\Support\Log;
use Nuwave\Lighthouse\Subscriptions\Support\Exceptions\InvalidSubscriptionQuery;
use Nuwave\Lighthouse\Subscriptions\Support\Protocol;
use Nuwave\Lighthouse\Subscriptions\Support\Parser;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;
use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;
use Illuminate\Contracts\Database\ModelIdentifier;

class TransportManager implements MessageComponentInterface, WsServerInterface
{

    use SerializesAndRestoresModelIdentifiers;

    /**
     * Connected clients.
     *
     * @var \SplObjectStorage
     */
    protected $clients;

    /**
     * Accepted protocols.
     *
     * @var array
     */
    protected $protocols = ['graphql-subscriptions', 'graphql-ws'];

    /**
     * Create new instance of Transport Manager.
     *
     * @return void
     */
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    /**
     * Get supported WebSocket sub-protocol(s)
     *
     * @return array
     */
    public function getSubProtocols()
    {
        return $this->protocols;
    }

    /**
     * Handle new GraphQL Subscription request.
     *
     * @param  ConnectionInterface $conn
     * @return void
     */
    public function onOpen(ConnectionInterface $conn)
    {
        try {
            $this->clients->attach($conn);

            Log::v('R', $conn, sprintf(
                "new client(%s) on {%s} - [%s] connected clients",
                $conn->resourceId,
                $conn->remoteAddress,
                count($this->clients)
            ));
        } catch (Exception $e) {
            Log::e($e);
        }
    }

    /**
     * Handle new subscription request.
     *
     * @param  ConnectionInterface $conn
     * @param  string $msg
     * @return void
     */
    public function onMessage(ConnectionInterface $conn, $msg)
    {
        try {
            Log::v('R', $conn, "Message from client: $msg");

            $data = json_decode($msg, true);

            switch (array_get($data, 'type')) {
                case Protocol::GQL_CONNECTION_INIT:
                    $this->handleConnectionInit($conn, $data);
                    break;
                case Protocol::GQL_START:
                    $this->handleStart($conn, $data);
                    break;
                case Protocol::GQL_STOP:
                    $this->handleStop($conn, $data);
                    break;
                default:
                    $conn->close();
            }
        } catch (Exception $e) {
            Log::e($e);
            $conn->close();
        }
    }

    /**
     * Remove connection.
     *
     * @param  ConnectionInterface $conn
     * @return void
     */
    public function onClose(ConnectionInterface $conn)
    {
        try {
            $this->subscriptions()->disconnect($conn->resourceId);
            $this->clients->detach($conn);

            Log::v('R', $conn, 'close', "Client({$conn->resourceId}) has disconnected");
        } catch (Exception $e) {
            Log::e($e);
        }
    }

    /**
     * Log and close connection on error.
     *
     * @param  ConnectionInterface $conn
     * @param  Exception $e
     * @return void
     */
    public function onError(ConnectionInterface $conn, Exception $e)
    {
        Log::e($e);
        $conn->close();
    }

    /**
     * Handle initialization of subscription.
     *
     * @param  ConnectionInterface $conn
     * @param  array $msg
     * @return void
     */
    protected function handleConnectionInit(ConnectionInterface $conn, array $msg)
    {
        try{
            $cookiesHeader = $conn->httpRequest->getHeader('Cookie');
            if(count($cookiesHeader)) {
                $cookies = \GuzzleHttp\Psr7\parse_header($cookiesHeader)[0];
                if (array_key_exists('laravel_token', $cookies)) {
                    $msg['Authorization'] = 'Bearer ' . $cookies['laravel_token'];
                }
            }

            $this->subscriptions()->store(
                $conn->resourceId,
                $msg
            );

            $this->sendMessage($conn, [
                'type' => Protocol::GQL_CONNECTION_ACK,
            ]);

        } catch (\Exception $e) {
            $this->sendMessage($conn, [
                'type' => Protocol::GQL_CONNECTION_ERROR,
                'payload' => [
                    'errors' => ['message' => $e->getMessage()],
                ],
            ]);
        }
    }

    /**
     * Handle start of subscription.
     *
     * @param  ConnectionInterface $conn
     * @param  array $msg
     * @return void
     */
    protected function handleStart(ConnectionInterface $conn, array $msg)
    {
        try {
            $subscriber = Subscriber::connection(
                $conn->resourceId,
                $msg,
                $this->subscriptions()->initialData($conn->resourceId)
            );

            $this->subscriptions()->subscribe($subscriber);

            Log::v('S', $conn, "Subscription: " . json_encode(compact('id', 'params')));

            $this->sendMessage($conn, [
                'type' => Protocol::GQL_CONNECTION_ACK
            ]);
        } catch (InvalidSubscriptionQuery $e) {
            $this->sendMessage($conn, [
                'type' => Protocol::GQL_ERROR,
                'id' => array_get($msg, 'id', 0),
                'payload' => [
                    'errors' => $e->getErrors(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->sendMessage($conn, [
                'type' => Protocol::GQL_ERROR,
                'id' => array_get($msg, 'id', 0),
                'payload' => [
                    'errors' => ['message' => $e->getMessage()],
                ],
            ]);
        }
    }

    /**
     * Handle end of subscription.
     *
     * @param  ConnectionInterface $conn
     * @param  array $msg
     * @return void
     */
    protected function handleStop(ConnectionInterface $conn, array $msg)
    {
        $this->subscriptions()->unsubscribe(
            $conn->resourceId, array_get($msg, 'id')
        );

        $this->sendMessage($conn, [
            'type' => Protocol::GQL_CONNECTION_ACK
        ]);

        Log::v('S', $conn, "unsubscribe from query");
    }

    /**
     * Send response to keep connection(s) alive.
     *
     * @return void
     */
    public function handleKeepAlive()
    {
        foreach ($this->clients as $client) {
            $this->sendMessage($client, [
                'type' => Protocol::GQL_CONNECTION_KEEP_ALIVE,
            ], false);
        }
    }

    /**
     * Broadcast subscription change.
     *
     * @param array $payload
     *
     * @return void
     */
    public function broadcast(array $payload)
    {
        $subscription = array_get($payload, 'subscription');
        $field = $this->field($subscription);
        $subscribers = $this->subscriptions()->subscribers($subscription);

        Log::v(' ', '', 'Subscribers ['.$subscription.']:');

        if (! $field || empty($subscribers)) {
            return;
        }

        $event = array_get($payload, 'event');

        foreach ($event as $key => $value) {
            if (is_array($value) && array_keys($value) == ["class", "id", "relations", "connection"]){
                $value = new ModelIdentifier($value['class'], $value['id'], $value['relations'], $value['connection']);
            }

            $event[$key] = $this->getRestoredPropertyValue($value);
        }

        $event = $field->transform($event);

        collect($this->clients)
            ->filter(function (ConnectionInterface $conn) use ($subscribers) {
                return isset($subscribers[$conn->resourceId]);
            })->filter(function (ConnectionInterface $conn) use ($event, $field, $subscribers) {
                return $field->filter($subscribers[$conn->resourceId], $event);
            })->each(function (ConnectionInterface $conn) use ($event, $subscribers) {
                $subscriber = $subscribers[$conn->resourceId];

                $this->sendMessage($conn, [
                    'id' => $subscriber->id(),
                    'type' => Protocol::GQL_DATA,
                    'payload' => $subscriber->resolve($event)
                ], true);
            });
    }

    /**
     * Send message to connection.
     *
     * @param  ConnectionInterface $conn
     * @param  array $message
     * @param  bool $log
     * @return void
     */
    protected function sendMessage(ConnectionInterface $conn, array $message, $log = true)
    {
        $connMessage = json_encode($message);

        if ($log) {
            Log::v('S', $conn, "sending message \"{$connMessage}\"");
        }

        $conn->send($connMessage);
    }

    /**
     * Get field instance for subscription.
     *
     * @param  string $subscription
     *
     * @return \Nuwave\Lighthouse\Subscriptions\Schema\Fields\SubscriptionField
     */
    protected function field($subscription)
    {
        return app(Parser::handle($subscription));
    }

    /**
     * Get instance of subscription manager.
     *
     * @return SubscriptionManager
     */
    protected function subscriptions()
    {
        return app(SubscriptionManager::class);
    }
}
