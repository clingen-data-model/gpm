<?php

namespace App\DataExchange;

use App\DataExchange\Kafka\KafkaConfig;
use Illuminate\Support\ServiceProvider;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;

class KafkaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->bindInstances();
    }

    private function bindInstances(): void
    {
        $this->app->bind(Producer::class, function () {
            $config = $this->app->make(KafkaConfig::class)->getConfig();

            return new Producer($config);
        });

        $this->app->bind(KafkaConsumer::class, function () {
            $conf = $this->app->make(KafkaConfig::class)->getConfig();
            $conf->set('auto.offset.reset', 'smallest');

            return new KafkaConsumer($conf);
        });
    }
}
