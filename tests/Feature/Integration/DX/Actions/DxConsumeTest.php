<?php

namespace Tests\Feature\Integration\DX\Actions;

use Tests\TestCase;
use App\DataExchange\DxMessage;
use Illuminate\Support\Facades\Bus;
use Tests\Dummies\FakeMessageStream;
use App\DataExchange\Actions\DxConsume;
use App\DataExchange\Contracts\MessageStream;
use Lorisleiva\Actions\Decorators\JobDecorator;
use App\DataExchange\Contracts\MessageProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group dx
 */
class DxConsumeTest extends TestCase
{
    use RefreshDatabase;

    protected array $messages;

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

    }

    /**
     * @test
     */
    public function consumes_dx_messages_and_dispatches_processor()
    {
        config(['queue.default' => 'redis']);
        Bus::fake();
        $action = app()->make(DxConsume::class);

        $action->handle(topics: ['a', 'b', 'c']);

        Bus::assertDispatchedTimes(JobDecorator::class, count($this->messages));
        Bus::assertDispatched(JobDecorator::class, function ($j) {
            return implementsInterface($j->getAction(), MessageProcessor::class);
        });
    }

    /**
     * @test
     */
    public function consums_limited_number_of_messages_if_limit_supplied()
    {
        config(['queue.default' => 'redis']);
        Bus::fake();
        $action = app()->make(DxConsume::class);

        $action->handle(topics: ['a', 'b', 'c'], limit: 1);

        Bus::assertDispatchedTimes(JobDecorator::class, 1);
        Bus::assertDispatched(JobDecorator::class, function ($j) {
            return implementsInterface($j->getAction(), MessageProcessor::class);
        });
    }

}
