<?php

namespace Tests\Unit\Events;

use App\Events\AbstractEvent;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Validator\GenericValidator;

final class AbstractEventTest extends TestCase
{
    private $event, $validator;

    public function setup(): void
    {
        $this->event = $this->getMockBuilder(AbstractEvent::class)
            ->getMockForAbstractClass();
        $this->validator = new GenericValidator();
    }

    /**
     * @test
     */
    public function it_should_return_an_event_uuid()
    {
        $this->assertIsString($this->event->getEventUuid());
        $this->assertTrue($this->validator->validate($this->event->getEventUuid()));
    }

    /**
     * @test
     */
    public function it_should_return_the_same_uuid_every_time()
    {
        $this->assertEquals($this->event->getEventUuid(), $this->event->getEventUuid());
    }
}
