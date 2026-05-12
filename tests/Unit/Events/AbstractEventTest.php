<?php

namespace Tests\Unit\Events;

use App\Events\AbstractEvent;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Validator\GenericValidator;
use PHPUnit\Framework\Attributes\Test;

final class AbstractEventTest extends TestCase
{
    private $event, $validator;

    public function setup(): void
    {
        $this->event = new AbstractEvent();
        $this->validator = new GenericValidator();
    }

    #[Test]
    public function it_should_return_an_event_uuid()
    {
        $this->assertIsString($this->event->getEventUuid());
        $this->assertTrue($this->validator->validate($this->event->getEventUuid()));
    }

    #[Test]
    public function it_should_return_the_same_uuid_every_time()
    {
        $this->assertEquals($this->event->getEventUuid(), $this->event->getEventUuid());
    }
}
