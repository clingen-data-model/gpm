<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\DataExchange\MessageHandlerFactory;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\UnsupportedIncomingMessage;
use App\DataExchange\Actions\ClassifiedRulesApprovedProcessor;
use App\DataExchange\Actions\ErrorMessageHandler;

class MessageHandlerFactoryTest extends TestCase
{
    public function setup():void
    {
        parent::setup();
        $this->factory = app()->make(MessageHandlerFactory::class);
    }
    
    /**
     * @test
     */
    public function throws_unsupportedIncomingMessage_exception_if_type_not_supported()
    {
        $message = new IncomingStreamMessage(['payload' => ['event' => 'snow-storm']]);

        $this->expectException(UnsupportedIncomingMessage::class);

        $this->factory->make($message);
    }
    

    /**
     * @test
     */
    public function returns_event_type_based_handler()
    {
        $message = new IncomingStreamMessage([
            'payload' => (object)[
                'event' => 'classified-rules-approved'
            ],
            'error_code' => 0
        ]);

        $this->assertInstanceOf(ClassifiedRulesApprovedProcessor::class, $this->factory->make($message));
    }

    /**
     * @test
     */
    public function returns_ErrorMessageHandler_if_error_code_not_0()
    {
        $message = new IncomingStreamMessage(['error_code' => 30]);
        $this->assertInstanceOf(ErrorMessageHandler::class, $this->factory->make($message));
    }
}
