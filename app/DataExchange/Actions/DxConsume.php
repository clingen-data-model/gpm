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
        private MessageStream $stream,
        private MessageProcessor $processor,
        private Dispatcher $jobBus
    )
    {
    }

    public function handle(array $topics, ?int $limit = null): void
    {
        $this->stream->addTopics($topics);
        $generator = is_null($limit) ? $this->stream->consume() : $this->stream->consumeSomeMessages($limit);
        foreach($generator as $message) {
            $this->jobBus->dispatch($this->processor::makeJob($message));
        }
    }

    public function asCommand(Command $command): void
    {
        $this->handle($command->argument('topic'), $command->option('limit'));
    }

}
