<?php

namespace App\DataExchange;

use RdKafka\KafkaConfig;
use \RdKafka\Producer;
use \RdKafka\KafkaConsumer as RdKafkaConsumer;
use Illuminate\Support\ServiceProvider;
use App\DataExchange\Kafka\KafkaConsumer;

class KafkaServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
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
            // TODO: MoveToKafkaServiceProvider?
            $conf = $this->app->make(KafkaConfig::class)->getConfig();
            $conf->set('auto.offset.reset', 'smallest');

            return new RdKafkaConsumer($conf);
        });
    }

}
