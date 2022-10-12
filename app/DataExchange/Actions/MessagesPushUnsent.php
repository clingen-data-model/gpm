<?php

namespace App\DataExchange\Actions;

use Illuminate\Console\Command;
use App\DataExchange\Actions\MessagePush;
use App\DataExchange\Models\StreamMessage;
use Lorisleiva\Actions\Concerns\AsCommand;

class MessagesPushUnsent
{
    use AsCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    public $commandSignature = 'dx:push-pending {--limit= : number of messages to be sent}';

    public function __construct(private MessagePush $messagePush)
    {
    }


    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Command $command)
    {
        $query = StreamMessage::unsent();
        if ($command->option('limit')) {
            $query->limit($command->option('limit'));
        }

        $progress = $command->getOutput()->createProgressBar($query->count());

        $query->get()->each(function ($message) use ($progress) {
            $this->pushMessage($message);

            $progress->advance();
        });
        $progress->finish();
        echo "\n";
    }

    private function pushMessage($message): void
    {
        if (config('queue.default') == 'sync') {
            $this->messagePush->handle($message);
            return;
        }
        MessagePush::dispatch($message);

    }

}
