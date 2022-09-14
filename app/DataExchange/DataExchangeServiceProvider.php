<?php

namespace App\DataExchange;

use ReflectionClass;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Facades\Actions;
use App\DataExchange\Listeners\PushMessage;
use App\DataExchange\Contracts\MessagePusher;
use App\DataExchange\Contracts\MessageStream;
use App\DataExchange\Kafka\KafkaMessageStream;
use App\DataExchange\Contracts\MessageProcessor;
use App\DataExchange\Actions\IncomingMessageStore;
use App\DataExchange\Actions\IncomingMessageProcess;
use App\DataExchange\MessageFactories\MessageFactoryInterface;
use App\DataExchange\MessagePushers\MessagePusherFactory;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class DataExchangeServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\DataExchange\Events\Created::class => [
            PushMessage::class
        ],
        \App\DataExchange\Events\Received::class => [
            IncomingMessageStore::class,
        ],
    ];

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->loadCommands(__DIR__.'/Commands');
        }

        $this->bindMessageClasses();

        if ($this->app->runningInConsole() && !$this->app->environment('testing')) {
            Actions::registerCommands(__DIR__.'/Actions');
        }
    }

    protected function bindMessageClasses()
    {
        $this->app->bind(MessagePusher::class, fn () => (new MessagePusherFactory)());
        $this->app->bind(MessageFactoryInterface::class, null);
        $this->app->bind(MessageStream::class, KafkaMessageStream::class);
        $this->app->bind(MessageProcessor::class, IncomingMessageProcess::class);
    }

    /**
     * Register all of the commands in the given directory.
     *
     * @param  array|string  $paths
     * @return void
     */
    protected function loadCommands($paths)
    {
        $commands = array_filter(
                        getClassesInPaths($paths, $this->app->getNamespace()),
                        function ($class) {
                            if (is_subclass_of($class, Command::class) &&
                                ! (new ReflectionClass($class))->isAbstract()) {
                                $commands[] = $class;
                            }
                        }
                    );
        $this->commands($commands);
    }
}
