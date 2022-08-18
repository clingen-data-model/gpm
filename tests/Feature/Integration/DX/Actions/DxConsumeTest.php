<?php

namespace Tests\Feature\Integration\DX\Actions;

use Tests\TestCase;
use App\DataExchange\DxMessage;
use Tests\Dummies\FakeMessageStream;
use App\DataExchange\Actions\DxConsume;
use Tests\Dummies\FakeMessageProcessor;
use Illuminate\Foundation\Testing\WithFaker;
use App\DataExchange\Contracts\MessageStream;
use App\DataExchange\Contracts\MessageProcessor;
use App\DataExchange\Models\IncomingStreamMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DxConsumeTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();

        $this->messages = [];
        $filesDir = base_path('tests/files/cspec');
        foreach(scandir($filesDir) as $idx => $file) {
            if ($file == '..' || $file == '.') {
                continue;
            }
            $this->messages[] = new DxMessage('a', time(), file_get_contents($filesDir.'/'.$file), $idx);
        }

        app()->bind(MessageStream::class, fn () => new FakeMessageStream($this->messages));

        $this->messageProcessor = new FakeMessageProcessor();
        app()->instance(MessageProcessor::class, $this->messageProcessor);
    }

    /**
     * @test
     */
    public function consumes_dx_messages_and_stores()
    {

        $action = app()->make(DxConsume::class);

        $action->handle(topics: ['a', 'b', 'c']);

        $this->assertEquals(count($this->messages), count($this->messageProcessor->messages));
    }

}
