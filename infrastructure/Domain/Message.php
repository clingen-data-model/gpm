<?php

namespace Infrastructure\Domain;

class Message implements MessageInterface
{
    public function equals(MessageInterface $other):bool
    {
        return $this->toArray() == $other->toArray();
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}