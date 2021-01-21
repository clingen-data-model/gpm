<?php

namespace Tests\Unit\Infrastructure\Adapters\Model;

use Ramsey\Uuid\Uuid;
use PHPUnit\Framework\TestCase;
use Infrastructure\Domain\Event;
use Tests\Fakes\TestDomainEvent;
use Infrastructure\Adapters\Models\StoredEvent;

class StoredEventTest extends TestCase
{
    /**
     * @test
     */
    public function can_transform_itself_to_the_correct_domain_event()
    {
        $aggregateId = Uuid::uuid4();
        $storedEvent = new StoredEvent();
        $storedEvent->type = TestDomainEvent::class;
        $storedEvent->aggregate_id = $aggregateId;
        $storedEvent->data = ['beans' => ['pinto', 'black', 'garbanzo']];

        $domainEvent = $storedEvent->toDomainEvent();
        $this->assertInstanceOf(TestDomainEvent::class, $domainEvent);
        $this->assertEquals(new TestDomainEvent(aggregateId: $aggregateId, beans: ['pinto', 'black', 'garbanzo']), $domainEvent);
    }

    /**
     * @test
     */
    public function can_create_a_StoredEvent_from_a_domain_event()
    {
        $aggregateId = Uuid::uuid4();
        $domainEvent = new TestDomainEvent(aggregateId: $aggregateId, beans: ['pinto', 'black', 'garbanzo']);
        $storedEvent = StoredEvent::fromDomainEvent($domainEvent, 1);

        $this->assertEquals($storedEvent->aggregate_id, $aggregateId);
        $this->assertEquals($storedEvent->data, ['beans'=>['pinto', 'black', 'garbanzo']]);
        $this->assertEquals($storedEvent->aggregate_version, 1);
    }
    
    
}
