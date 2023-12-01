<?php

namespace App\DataExchange\MessagePushers;

use App\DataExchange\Contracts\MessagePusher;
use App\DataExchange\Kafka\KafkaProducer;
use Illuminate\Support\Facades\Log;

class MessagePusherFactory
{
    public function __invoke(): MessagePusher
    {
        if (! config('dx.push-enable')) {
            return new DisabledPusher();
        }
        if (config('dx.driver') == 'kafka') {
            return app()->make(KafkaProducer::class);
        }
        if (config('dx.driver') == 'log') {
            return new MessageLogger();
        }

        Log::warning('No DataExchange driver set.  Defaulting to log driver');

        return new MessageLogger();
    }
}
