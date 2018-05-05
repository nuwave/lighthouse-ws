<?php

namespace Nuwave\Lighthouse\Subscriptions;

use Nuwave\Lighthouse\Subscriptions\Support\Parser;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Publisher;

class Server
{
    /**
     * Ratchet server.
     *
     * @var \Ratchet\Server\IoServer
     */
    protected $server;

    /**
     * Port WebSocket server is listening on.
     *
     * @var int
     */
    protected $port;

    /**
     * Keep alive interval.
     *
     * @var int
     */
    protected $keepAliveInterval;

    /**
     * Create new instance of websocket server.
     *
     * @param int $port
     * @param int $keepAliveInterval
     */
    public function __construct($port, $keepAliveInterval)
    {
        $this->port = $port;
        $this->keepAliveInterval = $keepAliveInterval;
    }

    /**
     * Run GraphQL Subscription Server.
     *
     * @return void
     */
    public function run()
    {
        $this->server = $this->runServer();

        $this->log();
        $this->server->run();
    }

    /**
     * Get instance of current event loop.
     *
     * @return \React\EventLoop\LoopInterface
     */
    public function loop()
    {
        return $this->server->loop;
    }

    /**
     * Run WS Server.
     *
     * @return \Ratchet\Server\IoServer
     */
    protected function runServer()
    {
        $parser = new Parser();
        $transport = app('graphql.ws-transport');
        $wsServer = new \Ratchet\WebSocket\WsServer($transport);

        $server = \Ratchet\Server\IoServer::factory(
            new \Ratchet\Http\HttpServer(
                $wsServer
            ),
            $this->port
        );

        if ($this->keepAliveInterval > 0) {
            $server->loop->addPeriodicTimer($this->keepAliveInterval, function () {
                Publisher::keepAlive();
            });
        }

        \Nuwave\Lighthouse\Subscriptions\WebSocket\Pusher::run($server->loop);

        return $server;
    }

    /**
     * Log start server message.
     *
     * @return void
     */
    protected function log()
    {
        \Nuwave\Lighthouse\Subscriptions\Support\Log::v(
            ' ',
            $this->server->loop,
            "Starting Websocket Service on port " . $this->port . " - {$this->keepAliveInterval}"
        );
    }
}
