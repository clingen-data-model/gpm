<?php

namespace Tests\Unit\Infrastructure\Adapters;

use PHPUnit\Framework\TestCase;

class EloquentEventStoreTest extends TestCase
{
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_example()
    {
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    // public function implements_EventStoreInterface()
    // {
    //     $es = new EloquentEventStore();

    //     $this->assertInstanceOf(EventStoreInterface::class, $es);
    // }

    /**
     * @test
     */
    // public function test_can_store_a_new_aggregate_with_its_events()
    // {
    //     $uuid = Uuid::uuid4();
    //     $event1 = new TestDomainEvent(aggregateId: $uuid, beans: ['pinto']);
    //     $event2 = new TestDomainEvent(aggregateId: $uuid, beans: ['black']);

    //     $agg = new FakeAggregate(events: [$event1, $event2]);
    //     $this->assertNotNull($agg->aggregateId);
    // }


}
