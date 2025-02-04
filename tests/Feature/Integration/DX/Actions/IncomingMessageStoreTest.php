<?php

namespace Tests\Feature\Integration\DX\Actions;

use App\DataExchange\Actions\IncomingMessageStore;
use App\DataExchange\DxMessage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IncomingMessageStoreTest extends TestCase
{
    use FastRefreshDatabase;

    /**
     * @test
     */
    public function stores_a_message_with_key_value_as_key()
    {
        $dxMessage = $this->makeDxMessage();

        $inStrMsg = app()->make(IncomingMessageStore::class)->handle($dxMessage);

        $expectedData = array_merge($this->makeData(), [
            'processed_at' => null
        ]);
        $this->assertDatabaseHas('incoming_stream_messages', $expectedData);
        $this->assertEquals($inStrMsg->key, $dxMessage->key);
    }

    /**
     * @test
     */
    public function stores_a_message_with_uuid_value_if_no_key_and_is_present()
    {
        $data = $this->makeData();
        $data['key'] = null;
        $dxMessage = $this->makeDxMessage($data);

        $inStrMsg = app()->make(IncomingMessageStore::class)->handle($dxMessage);

        $expectedData = array_merge($data, [
            'processed_at' => null,
            'key' => $dxMessage->payload->uuid
        ]);

        $this->assertDatabaseHas('incoming_stream_messages', $expectedData);
        $this->assertEquals($inStrMsg->key, $dxMessage->payload->uuid);
    }

    /**
     * @test
     */
    public function stores_message_with_topic_and_timestamp_as_key_if_no_key_or_uuid()
    {
        $data = $this->makeData();
        $payload = json_decode($data['payload']);
        unset($payload->uuid);
        $data['key'] = null;
        $data['payload'] = json_encode($payload);

        $dxMessage = $this->makeDxMessage($data);

        $inStrMsg = app()->make(IncomingMessageStore::class)->handle($dxMessage);

        $expectedData = array_merge($data, [
            'processed_at' => null,
            'key' => $dxMessage->topic.'-'.$dxMessage->timestamp
        ]);
        $this->assertDatabaseHas('incoming_stream_messages', $expectedData);
        $this->assertEquals($inStrMsg->key, $dxMessage->topic.'-'.$dxMessage->timestamp);
    }


    private function makeDxMessage($data = null)
    {
        $data = $data ?? $this->makeData();

        return new DxMessage(...$data);
    }

    private function makeData()
    {
        return [
            'topic' => 'test-topic',
            'timestamp' => 1234567,
            'payload' => json_encode([
                'test' => 'test',
                'beans' => 'fava',
                'uuid' => 'abc-def-hij-klmno'
            ]),
            'offset' => 0,
            'partition' => 0,
            'key' => '1234-5678-910123-12345'
        ];
    }
}
