<?php

namespace App\DataExchange\Actions;

use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\DataExchange\Contracts\MessageStream;
use App\DataExchange\Contracts\MessageProcessor;

class DxConsume
{
    use AsCommand;

    public $commandSignature = 'dx:consume {topic* : topic to be consumed} {--limit= : number of messages to consume}';

    public function __construct(
    )
    {
    }

    public function handle(array $topics, ?int $limit = null): void
    {
        $stream = app()->make(MessageStream::class);
        $processor = app()->make(MessageProcessor::class);
        $jobBus = app()->make(Dispatcher::class);
        $stream->addTopics($topics);
        $generator = is_null($limit) ? $stream->consume() : $stream->consumeSomeMessages($limit);
        foreach($generator as $message) {
            $jobBus->dispatch($processor::makeJob($message));
        }
    }

    public function asCommand(Command $command): void
    {
        $this->handle($command->argument('topic'), $command->option('limit'));
    }

}
