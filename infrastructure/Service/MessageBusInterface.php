<?php

namespace Infrastructure\Service;

use Infrastructure\Domain\MessageInterface;

interface MessageBusInterface
{
    public function handle(MessageInterface $message): void;
}
