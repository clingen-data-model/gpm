<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use App\Modules\Person\Models\Person;
use App\DataExchange\Actions\StreamMessageCreate;
use App\DataExchange\MessageFactories\DxMessageFactory;
use App\DataExchange\MessageFactories\MessageFactoryInterface;
use App\Modules\Person\Events\PersonCreated;

class CreateInitialPersonDxMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:dx:populate-person-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates person created events for all people currently in the system';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DxMessageFactory $messageFactory, StreamMessageCreate $streamMessageCreate)
    {
        config(['dx.push-enable' => false]);
        $people = Person::all();
        $progress = $this->output->createProgressBar($people->count());

        $people->each(function ($person) use ($messageFactory, $streamMessageCreate, $progress) {
                $event = new PersonCreated($person);
                $streamMessageCreate->handle(
                    topic: config('dx.topics.outgoing.gpm-person-events'),
                    message: $messageFactory->makeFromEvent($event)
                );
                $progress->advance();
            });

        $progress->finish();
        echo "\n";

        return 0;
    }
}
