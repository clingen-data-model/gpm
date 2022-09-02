<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\DataExchange\MessageHandlerFactory;
use App\DataExchange\Actions\ErrorMessageHandler;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\UnsupportedIncomingMessage;
use App\DataExchange\Actions\ClassifiedRulesApprovedProcessor;
use App\DataExchange\Actions\CspecDataSyncProcessor;

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
        $message = new IncomingStreamMessage([
            'topic' => 'test-test',
            'payload' => [
                'cspecDoc' => [
                    'status' => [
                        'event' => 'snow-storm'
                    ]
                ]
            ]
        ]);

        $this->expectException(UnsupportedIncomingMessage::class);

        $this->factory->make($message);
    }

    /**
     * @test
     */
    public function returns_CspecDataSyncProcessor_if_cspec_general_topic_by_default()
    {
        $message = new IncomingStreamMessage([
            'topic' => config('dx.topics.incoming.cspec-general'),
            'payload' => (object)[
                'cspecDoc' => [
                    'status' => [
                        'event' => 'pilot-rules-submitted'
                    ]
                ]
            ],
            'error_code' => 0
        ]);

        $this->assertInstanceOf(CspecDataSyncProcessor::class, $this->factory->make($message));
    }

    /**
     * @test
     */
    public function returns_event_type_based_handler()
    {
        $message = new IncomingStreamMessage([
            'payload' => (object)['cspecDoc' => [
                'status' => [
                    'event' => 'classified-rules-approved'
                ]
            ]],
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
