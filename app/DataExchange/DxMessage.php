<?php

namespace App\DataExchange;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use phpDocumentor\Reflection\Types\Boolean;

class DxMessage implements Arrayable, JsonSerializable, Jsonable
{
    public function __construct(
        public String $topic,
        public $timestamp,
        private $payload,
        public int $offset,
        public int $partition = 0,
        public ?String $key = null,
        public ?int $errorCode = null,
        public ?String $errorString = null
    ) {
    }

    public function __get($key)
    {
        if ($key === 'payload') {
            return json_decode($this->payload);
        }
    }

    public function isErrorMessage(): bool
    {
        return !is_null($this->errorCode);
    }

    public function isReportableError(): bool
    {
        return $this->isErrorMessage && !$this->isEndOfFile() && !$this->isTimeOut();
    }

    public function isEndOfFile(): bool
    {
        return $this->errorCode == RD_KAFKA_RESP_ERR__PARTITION_EOF;
    }

    public function isTimeOut(): bool
    {
        return $this->errorCode == RD_KAFKA_RESP_ERR__TIMED_OUT;
    }


    public static function createFromRdKafkaMessage(\RdKafka\Message $message)
    {
        return new static(
            topic: $message->topic_name ?? '--',
            timestamp: $message->timestamp,
            partition: $message->partition,
            payload: $message->payload,
            key: $message->key,
            offset: $message->offset,
            errorCode: $message->err,
            errorString: $message->errstr()
        );
    }

    public function toArray(): array
    {
        return [
            'topic' => $this->topic,
            'key' => $this->key,
            'timestamp' => $this->timestamp,
            'payload' => $this->__get('payload'),
            'offset' => $this->offset
        ];
    }

    public function jsonSerialize(): mixed
    {
        return json_encode($this->toArray());
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }


}
