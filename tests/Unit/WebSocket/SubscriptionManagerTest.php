<?php

namespace Tests\Unit\WebSocket;

use Tests\TestCase;
use Nuwave\Lighthouse\Subscriptions\WebSocket\SubscriptionManager;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Storage\MemoryStorage;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Subscriber;

class SubscriptionManagerTest extends TestCase
{
    /**
     * Instance of subscription manager
     *
     * @var SubscriptionManager
     */
    protected $manager;

    /**
     * Set up test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->manager = new SubscriptionManager($this->app);
    }

    /**
     * @test
     */
    public function itCanResolveDefaultDriver()
    {
        $driver = $this->manager->driver();

        $this->assertInstanceOf(MemoryStorage::class, $driver);
    }

    /**
     * @test
     */
    public function itCanResolveSubscribers()
    {
        $this->manager->store(10);
        $this->manager->store(20);

        $this->manager->subscribe($this->subscriber(10));
        $this->manager->subscribe($this->subscriber(20));

        $subscribers = $this->manager->subscribers('postCreated');

        $this->assertCount(2, $subscribers);
        $this->assertArrayHasKey(10, $subscribers);
        $this->assertArrayHasKey(20, $subscribers);
    }

    /**
     * @test
     */
    public function itCanBeExtended()
    {
        $manager = new class {
            public function store($id)
            {
                return 'bar';
            }
        };

        $this->manager->extend('foo', function () use ($manager) {
            return $manager;
        });

        $this->manager->setDefaultDriver('foo');

        $driver = $this->manager->driver();
        $bar = $driver->store(1);

        $this->assertEquals('bar', $bar);
    }

    /**
     * Generate a new subscriber.
     *
     * @param  int $connId
     * @param  int $id
     * @param  array   $payload
     * @return Subscriber
     */
    protected function subscriber($connId = 1, $id = 1, $payload = [])
    {
        return Subscriber::connection($connId, [
            'id' => $id,
            'payload' => array_merge([
                'query' => 'subscription { postCreated { id } }',
                'variables' => [],
                'operationName' => []
            ], $payload)
        ]);
    }
}
