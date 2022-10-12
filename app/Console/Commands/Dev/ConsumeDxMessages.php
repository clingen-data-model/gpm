<?php

namespace App\Console\Commands\Dev;

use Exception;
use \RdKafka\KafkaConsumer as RdKafkaConsumer;
use Illuminate\Console\Command;
use App\DataExchange\Kafka\KafkaConfig;
use App\DataExchange\Kafka\KafkaMessageStream;

class ConsumeDxMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:dx:consume {actions*} {--topic=* : Topic to consume} {--offset= : offset to assign} {--limit= : number of messages to read before stopping.} {--only-offset : Only update the topic offset} {--print-offset : print the offset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'A command line client for reviewing messages from the data exchange.';

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
    public function handle()
    {
        $offset = $this->option('offset');
        $onlyOffset = $this->option('only-offset');
        $limit = $onlyOffset ? 0 : $this->option('limit');
        $actions = $this->argument('actions');

        $kafkaConsumer = $this->buildKafkaConsumer($offset);

        $messageStream = new KafkaMessageStream($kafkaConsumer);

        $topics = $this->option('topic', null);
        if (!$topics) {
            $topics = $this->solicitTopics($kafkaConsumer);
        }

        foreach ($topics as $topic) {
            $messageStream->addTopic($topic);
        }

        $count = 0;
        $generator = !is_null($limit)
                        ? $messageStream->consumeSomeMessages($limit)
                        : $messageStream->consume();

        if (!is_null($limit)) {
            $this->info('Will read '.$limit.' messages.');
        }

        foreach ($generator as $message) {
            $count++;
            foreach ($actions as $action) {
                if (method_exists($this, $action)) {
                    $this->$action($message);
                }
            }
        }

        $this->info($count.' messages read.');
    }

    /**
     * Print the payload to the stdOut
     *
     * @param mixed $message
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     */
    private function printPayload($message)
    {
        $this->info('Payload:' . json_encode($message->payload, JSON_PRETTY_PRINT));
    }

    /**
     * Print the offset to the stdOut
     *
     * @param mixed $message
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     */
    private function printOffset($message)
    {
        $this->info('Offset: '.$message->offset);
    }

    /**
     * Print the status to the stdOut
     *
     * @param mixed $message
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     */
    private function printStatus($message)
    {
        $this->info('Status: '.json_encode($message->payload->cspecDoc->status->current, JSON_PRETTY_PRINT));
    }

    /**
     * Print the event name to the stdOut
     *
     * @param mixed $message
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     */
    private function printEvent($message)
    {
        $this->info('Event: '.json_encode($message->payload->cspecDoc->status->event, JSON_PRETTY_PRINT));
    }

     /**
     * Print the message key to the stdOut
     *
     * @param mixed $message
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     */
    private function printKey($message)
    {
        $this->info('Key: '.$message->key);
    }

    /**
     * Store the message as to it's own file
     *
     * @param mixed $message
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     *
     */
    private function persistSingleMessage($message): void
    {
        $filepath = base_path('consumed_messages/'.($message->key ?? time()).'.json');
        dump($filepath);
        file_put_contents($filepath, $message->toJson(JSON_PRETTY_PRINT));
    }

    private function solicitTopics(\RdKafka\KafkaConsumer $kafkaConsumer)
    {
        $availableTopics = [];

        foreach ($kafkaConsumer->getMetadata(true, null, 60e3)->getTopics() as $idx => $topic) {
            $availableTopics[$idx] = $topic->getTopic();
        }

        return $this->choice(
            question: 'Which topics do you want to consume',
            choices: $availableTopics,
            multiple: true
        );
    }

    private function buildKafkaConsumer(?int $offset = null): RdKafkaConsumer
    {
        $rdKafkaConfig = $this->buildKafkaConfig($offset);
        return new RdKafkaConsumer($rdKafkaConfig);
    }

    private function buildKafkaConfig(?int $offset = null)
    {
        $config = app()->make(KafkaConfig::class);
        $config->setRebalanceCallback(
            function (RdKafkaConsumer $consumer, $err, array $topicPartitions = null) use ($offset) {
                switch ($err) {
                    case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                        echo "\nAssign partions...\n";
                        $consumer->assign($topicPartitions);

                        foreach ($topicPartitions as $tp) {
                            $this->commitOffset($consumer, $tp, $offset);
                            echo $tp->getTopic().' offset now at '.$tp->getOffset()."\n";
                        }

                        break;

                    case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                        $assignments = $consumer->getAssignment();
                        $consumer->assign(null);
                        break;

                    default:
                        throw new Exception($err);
                }
            }
        );

        return $config->getConfig();
    }

    private function commitOffset($consumer, $topicPartition, $offset, $attempt = 0)
    {
        if (is_null($offset)) {
            $this->info("Don't update offset.");
            return;
        }
        if ($offset >= 0) {
            $this->info("Committing offset set to $offset for topic ".$topicPartition->getTopic()." on partition ".$topicPartition->getPartition()."...");
        }
        $topicPartition->setOffset($offset);
        $consumer->commit([$topicPartition]);
    }
}
