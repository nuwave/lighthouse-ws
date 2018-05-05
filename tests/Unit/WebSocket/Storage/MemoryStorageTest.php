<?php

namespace Tests\Unit\WebSocket\Storage;

use Nuwave\Lighthouse\Subscriptions\WebSocket\Storage\MemoryStorage;
use Nuwave\Lighthouse\Subscriptions\WebSocket\Subscriber;
use Tests\TestCase;

class MemoryStorageTest extends TestCase
{
    /**
     * Instance of memory storage.
     *
     * @var MemoryStorage
     */
    protected $storage;

    /**
     * Set up test environment.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->storage = new MemoryStorage();
    }

    /**
     * @test
     */
    public function itCanAddAConnection()
    {
        $this->storage->store(10);
        $this->storage->store(100);

        $connections = $this->storage->all();

        $this->assertCount(2, $connections);
        $this->assertArrayHasKey(10, $connections);
        $this->assertArrayHasKey(100, $connections);
    }

    /**
     * @test
     */
    public function itCanGetInitialData()
    {
        $init = ['Authorization' => 'Bearer foo'];

        $this->storage->store(1, $init);
        $this->storage->subscribe($this->subscriber());

        $this->assertEquals($init, $this->storage->initialData(1));
    }

    /**
     * @test
     */
    public function itCanAddSubscriptions()
    {
        $this->storage->store(1);
        $this->storage->store(2);

        $this->storage->subscribe($this->subscriber());
        $this->storage->subscribe($this->subscriber(1, 2, [
            'query' => 'subscription { postUpdated(id: $id) { title } }'
        ]));
        $this->storage->subscribe($this->subscriber(2));

        $connections = $this->storage->all();
        $this->assertCount(2, $connections);
        $this->assertCount(2, $connections[1]['subscriptions']);
        $this->assertCount(1, $connections[2]['subscriptions']);
    }

    /**
     * @test
     */
    public function itCanUnsubscribe()
    {
        $this->storage->store(1);

        $this->storage->subscribe($this->subscriber());
        $this->storage->subscribe($this->subscriber(1, 2, [
            'query' => 'subscription { postUpdated(id: $id) { title } }'
        ]));

        $this->storage->unsubscribe(1, 2);

        $connections = $this->storage->all();
        $this->assertCount(1, $connections);
        $this->assertCount(1, $connections[1]['subscriptions']);
    }

    /**
     * @test
     */
    public function itCanDisconnect()
    {
        $this->storage->store(1);
        $this->storage->subscribe($this->subscriber());
        $this->storage->disconnect(1);

        $connections = $this->storage->all();
        $this->assertEmpty($connections);
    }

    /**
     * @test
     */
    public function itCanGetSubscribersBySubscription()
    {
        $this->storage->store(10);
        $this->storage->store(20);

        $this->storage->subscribe($this->subscriber(10));
        $this->storage->subscribe($this->subscriber(10, 2, [
            'query' => 'subscription { postUpdated(id: $id) { title } }'
        ]));
        $this->storage->subscribe($this->subscriber(20));

        $subscribers = $this->storage->get('postCreated');
        $this->assertCount(2, $subscribers);
        $connections = collect($subscribers)->map(function ($subscriber) {
            return $subscriber->connectionId();
        })->toArray();

        $this->assertTrue(in_array(10, $connections));
        $this->assertTrue(in_array(20, $connections));
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
