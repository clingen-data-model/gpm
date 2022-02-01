<?php

use Ramsey\Uuid\Uuid;

require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
$topic = isset($options['topic']) ? $options['topic'] : '';

$conf = new RdKafka\Conf();

// Set a rebalance callback to log partition assignments (optional)
$conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
    switch ($err) {
        case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
            echo "Assign: ";
            var_dump($partitions);
            $kafka->assign($partitions);
            break;

         case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
             echo "Revoke: ";
             var_dump($partitions);
             $kafka->assign(null);
             break;

         default:
            throw new \Exception($err);
    }
});

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


$rk = new RdKafka\Producer($conf);
$rk->setLogLevel(LOG_DEBUG);
$rk->addBrokers(env('DX_BROKER'));

$topic = $rk->newTopic($topic);

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
    $rk->poll(0);
}


while ($rk->getOutQLen() > 0) {
    $rk->poll(50);
}