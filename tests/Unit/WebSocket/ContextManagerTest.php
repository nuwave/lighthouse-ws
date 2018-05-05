<?php

namespace Tests\Unit\WebSocket;

use Tests\TestCase;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Subscriber;
use Nuwave\Lighthouse\Subscriptions\WebSocket\ContextManager;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Context\SubscriberContext;

class ContextManagerTest extends TestCase
{
    /**
     * Context manager instance.
     *
     * @var ContextManager
     */
    protected $manager;

    /**
     * Setup test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->manager = new ContextManager($this->app);
    }

    /**
     * @test
     */
    public function itCanResolveDefaultDriver()
    {
        $context = $this->manager->build($this->subscriber());

        $this->assertInstanceOf(SubscriberContext::class, $context);
    }

    /**
     * @test
     */
    public function itCanResolveCustomDriver()
    {
        $builder = function () {
            return new class {
                public $foo = 'bar';
            };
        };

        $this->manager->extend('custom', $builder);
        $this->manager->setDefaultDriver('custom');

        $context = $this->manager->build($this->subscriber());
        $this->assertEquals('bar', $context->foo);
    }

    /**
     * Create instance of subscriber.
     *
     * @return Subscriber
     */
    protected function subscriber()
    {
        return Subscriber::connection(1, [
            'id' => 1,
            'payload' => [
                'query' => 'subscription { onPostCreated { id } }',
                'variables' => [],
                'operationName' => ''
            ]
        ]);
    }
}
