<?php

use Exception;
use RdKafka\Conf;
use App\DataExchange\Exceptions\StreamingServiceException;

require __DIR__ . '/vendor/autoload.php';

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
        }
        $options[$name] = $value;
        continue;
    }
    $arguments[] = $arg;
}

$topics = isset($options['topic']) ? explode(',', $options['topic']) : [];
$offset = (int)(isset($options['offset']) ? $options['offset'] : -1);
$limit = isset($options['limit']) ? (int)$options['limit'] : false;
$writeToDisk = isset($options['write-to-disk']) ? (int)$options['write-to-disk'] : false;
$saveToCsv = isset($options['save-to-csv']) ? (int)$options['save-to-csv'] : false;
$noPrint = isset($options['no-print']) ? (int)$options['no-print'] : false;

if (file_exists(__DIR__.'/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}


function rSortByKeys($array)
{
    $obj = false;
    if (is_object($array)) {
        $array = (array)$array;
        $obj = true;
    }
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = rSortByKeys($value);
        }
    }
    ksort($array);
    if ($obj) {
        return (object)$array;
    }
    return $array;
}


function commitOffset($consumer, $topicPartition, $offset, $attempt = 0)
{
    if ($offset == 0) {
        echo "\nCommitting offset set to $offset for topic ".$topicPartition->getTopic()." on partition ".$topicPartition->getPartition()."...\n";
        return;
    }
    echo "\nDon't update offset.\n";
    // $topicPartition = new RdKafka\TopicPartition($topic, 0, $offset);
    $topicPartition->setOffset($offset);
    $consumer->commit([$topicPartition]);
}

function configure($offset)
{
    $conf = new Conf();

    // Configure the group.id. All consumer with the same group.id will consume
    // different partitions.
    $conf->setErrorCb(function ($kafka, $err, $reason) {
        throw new StreamingServiceException("Kafka producer error: ".rd_kafka_err2str($err)." (reason: ".$reason.')');
    });
    
    $conf->setStatsCb(function ($kafka, $json, $json_len) {
        Log::info('Kafka Stats ', json_decode($json));
    });
    
    $conf->setDrMsgCb(function ($kafka, $message) {
        if ($message->err) {
            throw new StreamingServiceException('DrMsg: '.rd_kafka_err2str($message->err));
        }
    });

    $conf->set('auto.offset.reset', 'beginning');
    
    // Set a rebalance callback to log partition assignments (optional)
    $conf->setRebalanceCb(function (RdKafka\KafkaConsumer $consumer, $err, array $topicPartitions = null) use ($offset) {
        switch ($err) {
            case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                echo "\nAssign partions...\n";
                $consumer->assign($topicPartitions);
                
                foreach ($topicPartitions as $tp) {
                    commitOffset($consumer, $tp, $offset);
                }
    
                break;
    
            case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                $assignments = $consumer->getAssignment();
                $consumer->assign(null);
                break;
    
            default:
                throw new Exception($err);
        }
    });

    return $conf;
}

function printMessage($message)
{
    echo "\n-----\n";
    echo(json_encode([
        'len' => $message->len,
        'topic_name' => $message->topic_name,
        'timestamp' => $message->timestamp,
        'partition' => $message->partition,
        'payload' => json_decode($message->payload),
        'key' => $message->key,
        'offset' => $message->offset,
    ], JSON_PRETTY_PRINT));
}

function configureForConfluent($offset)
{
    $conf = configure($offset);

    echo "\nsetting group to ".env('DX_GROUP', 'unc_staging')." for ".env('DX_BROKER')."...\n";
    $conf->set('group.id', env('DX_GROUP', 'unc_staging'));
    
    $conf->set('security.protocol', 'sasl_ssl');
    $conf->set('metadata.broker.list', env('DX_BROKER'));
    $conf->set('sasl.mechanism', 'PLAIN');
    echo "\nAuthenticating ".env('DX_BROKER')." with DX_USERNAME (".env('DX_USERNAME').") and DX_PASSWORD (see .env)\n";
    $conf->set('sasl.username', env('DX_USERNAME'));
    $conf->set('sasl.password', env('DX_PASSWORD'));

    return $conf;
}

function writeToDisk($message)
{
    $messageDir = __DIR__.'/consumed_messages';
    if (!file_exists($messageDir)) {
        mkdir($messageDir);
        echo "\nmade ".$messageDir.' directory'."\n";
    }

    $filename = $message->key ?? 'null-key-'.$message->offset;
    $filePath = $messageDir.'/'.$filename.'.json';
    file_put_contents($filePath, json_encode(json_decode($message->payload), JSON_PRETTY_PRINT));
}

$csvHandle = null;

function saveToCsv($message)
{
    global $csvHandle;

    $payload = json_decode($message->payload);
    $eventType = $payload->event_type;
    $data = $payload->data;

    if (!$csvHandle) {
        $csvHandle = fopen('kafkaConsumedMessages.csv', 'c');
        fputcsv($csvHandle, [
            'offset',
            'timestamp',
            'expert_panel',
            'event_type',
        ]);
    }
    $lineData = [
        $message->offset,
        $message->timestamp,
        $data->expert_panel->affiliation_id,
        $eventType,
    ];
    fputcsv($csvHandle, $lineData);
}

function closeCsvHandle()
{
    global $csvHandle;
    if ($csvHandle) {
        fclose($csvHandle);
    }
}

$conf = configureForConfluent($offset);

$consumer = new RdKafka\KafkaConsumer($conf);

if (count($topics) == 0 || array_key_exists('list-topics', $options)) {
    $availableTopics = $consumer->getMetadata(true, null, 60e3)->getTopics();
    echo "Available Topics: \n";
    foreach ($availableTopics as $idx => $avlTopic) {
        echo $idx.': '.$avlTopic->getTopic()."\n";
    }

    echo "Enter the number of the topic(s) do you want to join? (comma-delimit for > 1):\n";
    $stdin = fopen('php://stdin', 'r');
    $line = fgets($stdin);
    $topicIndexes = explode(',', trim($line));
    $topics = [];
    foreach ($availableTopics as $idx => $avlTopic) {
        if (in_array($idx, $topicIndexes)) {
            $topics[] = $avlTopic->getTopic();
        }
    }
    fclose($stdin);
}

if (array_key_exists('dont-listen', $options)) {
    exit;
}

// Subscribe to topic 'test'
echo "**Subscribing to the following topics:\n".implode("\n  ", $topics)."...\n";
$consumer->subscribe($topics);
echo "\nWaiting for partition assignment...\n";

$count = 0;
$keys = [];

$timedOut = false;
while (true) {
    $message = $consumer->consume(10000);
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            $payload = json_decode($message->payload);
            if (is_array($payload)) {
                $payload = rSortByKeys($payload);
            }

            if ($writeToDisk) {
                writeToDisk($message);
            }

            if ($saveToCsv) {
                saveToCsv($message);
            }

            if (!$noPrint) {
                printMessage($message);
            }

            if (!isset($keys[$message->key])) {
                $keys[$message->key] = [];
            }
            $keys[$message->key][] = json_encode($payload, JSON_PRETTY_PRINT);
            $count++;
            if ($limit && $count > $limit) {
                echo "\nReached limit $limit\n";
                break 2;
            }
            $timedOut = false;
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo "\n\n**No more messages; will wait for more...\n\n";
            $timedOut = false;
            break;
            // echo "\n\nFound all messages. Closing for now.\n\n";
            // break 2;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            if (!$timedOut) {
                echo "**timed out - waiting for messages...\n";
                // echo "Timed out\n";
                $timedOut = true;
            }
            break;
        case RD_KAFKA_RESP_ERR__FAIL:
            echo "**Failed to communicate with broker\n";
            $timedOut = false;
            break;
        case RD_KAFKA_RESP_ERR__BAD_MSG:
            echo "**Bad message format\n";
            $timedOut = false;
            break;
        case RD_KAFKA_RESP_ERR__RESOLVE:
            echo "**Host resolution failure";
            $timedOut = false;
            break;
        case RD_KAFKA_RESP_ERR__UNKNOWN_TOPIC:
            echo "**unknown topic\n";
            $timedOut = false;
            break;
        case RD_KAFKA_RESP_ERR_INVALID_GROUP_ID:
            echo "**invalid group id\n";
            $timedOut = false;
            break;
        case RD_KAFKA_RESP_ERR_GROUP_AUTHORIZATION_FAILED:
            echo "**group auth failed\n";
            $timedOut = false;
            break;
        default:
            echo "**Unknown Error: ".$message->err."\n";
            $timedOut = false;
            break;

    }
}

echo count($keys)." keys that have the multple messages\n";
foreach ($keys as $key => $payloads) {
    if (count($payloads) > 1) {
        echo $key." has ".count($payloads)." messages.\n";
        for ($i=0; $i < count($payloads); $i++) {
            if ($i == 0) {
                continue;
            }
            $diff = xdiff_string_diff($payloads[($i-1)], $payloads[$i]);
            echo(($diff) ? $diff : 'NO DIFFERENCE')."\n";
        }
        echo "-------\n";
    }
}

closeCsvHandle();
