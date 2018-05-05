<?php

namespace Nuwave\Lighthouse\Subscriptions\WebSocket\Context;

use Nuwave\Lighthouse\Subscriptions\WebSocket\Subscriber;
use Nuwave\Lighthouse\Subscriptions\Support\Contracts\SubscriptionContext;
use Illuminate\Http\Request;

class SubscriberContext implements SubscriptionContext
{
    /**
     * Websocket subscriber.
     *
     * @var Subscriber
     */
    public $subscriber;

    /**
     * WS request instance.
     *
     * @var \Illuminate\Http\Request
     */
    public $request;

    /**
     * Create new instance of subscriber context.
     *
     * @param Subscriber $subscriber
     */
    public function setSubscriber(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    /**
     * Build subscription context.
     *
     * @return void
     */
    public function build()
    {
        $payload = $this->subscriber->initialPayload();
        $uri = '/'.config('lighthouse.route_name');
        $data = array_except($payload, ['Authorization']);

        $this->request = \Illuminate\Http\Request::create($uri, 'WS', $data);
    }
}
