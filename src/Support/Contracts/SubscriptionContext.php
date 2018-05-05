<?php

namespace Nuwave\Lighthouse\Subscriptions\Support\Contracts;

use Nuwave\Lighthouse\Subscriptions\WebSocket\Subscriber;

interface SubscriptionContext
{
    /**
     * Create new instance of subscriber context.
     *
     * @param Subscriber $subscriber
     */
    public function setSubscriber(Subscriber $subscriber);

    /**
     * Build subscription context.
     *
     * @return void
     */
    public function build();
}
