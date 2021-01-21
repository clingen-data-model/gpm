<?php

namespace Tests\Unit\Infrastructure\Adapters;

use Tests\TestCase;
use Ramsey\Uuid\Uuid;
use Tests\Fakes\FakeAggregate;
use Tests\Fakes\TestDomainEvent;
use Infrastructure\Adapters\EloquentEventStore;
use Infrastructure\Adapters\Models\StoredEvent;
use Infrastructure\Adapters\EventStoreInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Adapters\ConcurrentWriteException;

class EloquentEventStoreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function implements_EventStoreInterface()
    {
        $es = new EloquentEventStore();

        $this->assertInstanceOf(EventStoreInterface::class, $es);
    }

    /**
     * @test
     */
    public function test_can_store_a_new_aggregate_with_its_events()
    {
        $uuid = Uuid::uuid4()->toString();

        $agg = new FakeAggregate();
        $agg->addTestEvent(new TestDomainEvent(aggregateId: $uuid, beans: ['pinto']));
        $agg->addTestEvent(new TestDomainEvent(aggregateId: $uuid, beans: ['black']));

        $this->assertNotNull($agg->aggregateId);

        $es = new EloquentEventStore();

        $es->commitChanges($agg);

        $this->assertDatabaseHas('stored_events', [
            'aggregate_id' => $uuid,
            'data' => json_encode(['beans'=>['pinto']]),
            'aggregate_version' => 1
        ]);
        $this->assertDatabaseHas('stored_events', [
            'aggregate_id' => $uuid,
            'data' => json_encode(['beans'=>['black']]),
            'aggregate_version' => 1
        ]);
    }

    /**
     * @test
     */
    public function throws_ConcurrentWriteException_before_writing_when_version_has_changed()
    {
        $es = new EloquentEventStore();
        $uuid = Uuid::uuid4()->toString();


        $agg = new FakeAggregate();
        $agg->addTestEvent(new TestDomainEvent(aggregateId: $uuid, beans: ['pinto']));
        $es->commitChanges($agg);

        \DB::table('stored_events')->insert([
            'uuid' => Uuid::uuid4()->toString(), 
            'aggregate_id' => $uuid, 
            'aggregate_version' => 2, 
            'type'=>Tests\Fakes\TestDomainEvent::class, 
            'data'=>json_encode(['beans'=>['garbanzo']])
        ]);

        $agg->addTestEvent(new TestDomainEvent(aggregateId: $uuid, beans: ['black']));
        $es->commitChanges($agg);


        $es = new EloquentEventStore();

        $this->expectException(ConcurrentWriteException::class);

        $es->commitChanges($agg);
        
    }
    



}
