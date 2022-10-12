<?php

namespace App\DataExchange;

use ReflectionClass;
use Illuminate\Console\Command;
use Lorisleiva\Actions\Facades\Actions;
use App\DataExchange\Actions\MessagePush;
use App\DataExchange\Listeners\PushMessage;
use App\DataExchange\Contracts\MessagePusher;
use App\DataExchange\Contracts\MessageStream;
use App\DataExchange\Kafka\KafkaMessageStream;
use App\DataExchange\Contracts\MessageProcessor;
use App\DataExchange\Actions\IncomingMessageStore;
use App\DataExchange\Actions\IncomingMessageProcess;
use App\DataExchange\MessagePushers\MessagePusherFactory;
use App\DataExchange\MessageFactories\MessageFactoryInterface;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class DataExchangeServiceProvider extends ServiceProvider
{
    protected $listen = [
        \App\DataExchange\Events\Created::class => [
            MessagePush::class
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

}
