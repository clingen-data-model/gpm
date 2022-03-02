<?php

use Ramsey\Uuid\Uuid;

require __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__.'/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

echo "You are producing as group ".env('DX_GROUP')."\n";

$argments = [];
$options = [];
foreach ($argv as $idx => $arg) {
    if ($idx == 0) {
        continue;
    }
    if (substr($arg, 0, 2) == '--') {
        $name = substr($arg, 2);
        $value = true;
        if (preg_match('/=/', $name)) {
            [$name, $value] = explode('=', $name);
        } elseif (isset($argv[$idx+1])) {
            $value = $argv[$idx+1];
        }
        $options[$name] = $value;
        continue;
    }
    $arguments[] = $arg;
}
function getTopicName($options)
{
    if (isset($options['topic'])) {
        return $options['topic'];
    }

    echo "Topic:\n";
    $stdin = fopen('php://stdin', 'r');
    $topicName = fgets($stdin);
    return trim($topicName);
}

$topicName = getTopicName($options);

$conf = new RdKafka\Conf();

$conf->setErrorCb(function ($kafka, $err, $reason) {
    throw new StreamingServiceException("Kafka producer error: ".rd_kafka_err2str($err)." (reason: ".$reason.')');
});

$conf->setStatsCb(function ($kafka, $json, $json_len) {
    Log::info('Kafka Stats ', json_decode($json));
});

$conf->setDrMsgCb(function ($kafka, $message) {
    if ($message->err) {
        throw new StreamingServiceException('DrMsg: '.rd_kafka_err2str($err));
    }
});

$conf->set('security.protocol', 'sasl_ssl');
$conf->set('sasl.mechanism', 'PLAIN');
$conf->set('sasl.username', env('DX_USERNAME'));
$conf->set('sasl.password', env('DX_PASSWORD'));
$conf->set('group.id', env('DX_GROUP'));
$conf->set('metadata.broker.list', env('DX_BROKER'));


$producer = new RdKafka\Producer($conf);
$producer->setLogLevel(LOG_DEBUG);
$producer->addBrokers(env('DX_BROKER'));

$topic = $producer->newTopic($topicName);

$stdin = fopen("php://stdin", "r");

echo "starting input loop...\n";
while (true) {
    $line = trim(fgets($stdin));
    if (in_array($line, ['quit', 'exit'])) {
        break;
    }
    dump(trim($line));
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, json_encode(['test' => trim($line)]), Uuid::uuid4()->toString());
    
    echo "tried to produce message '$line'\n";
    $producer->poll(0);
}


for ($flushRetries = 0; $flushRetries < 10; $flushRetries++) {
    $result = $producer->flush(10000);
    if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
        break;
    }
}

// while ($producer->getOutQLen() > 0) {
//     $producer->poll(50);
// }
