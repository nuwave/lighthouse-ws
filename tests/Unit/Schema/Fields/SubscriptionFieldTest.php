<?php

namespace Tests\Unit\Schema\Fields;

use Nuwave\Lighthouse\Subscriptions\Schema\Fields\SubscriptionField;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Subscriber;
use Tests\TestCase;

class SubscriptionFieldTest extends TestCase
{
    /**
     * @test
     */
    public function itCanGenerateSubscriptionKey()
    {
        $query = "subscription { postCreated { id title } }";
        $variables = [];

        $this->assertEquals('postCreated', $this->subscription()->key(compact('query', 'variables')));
    }

    /**
     * Get subscription field instance.
     *
     * @return SubscriptionField
     */
    protected function subscription()
    {
        return new class extends SubscriptionField {
            public function can(Subscriber $subscriber, $context, array $args = []) 
            {
                return true;
            }

            public function resolve($root, array $args, $context)
            {
                return null;
            }
        };
    }
}
