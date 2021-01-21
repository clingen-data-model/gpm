<?php

namespace Infrastructure\Service;

use Exception;
use Infrastructure\Domain\Event;
use Infrastructure\Domain\Command;
use Infrastructure\Domain\MessageInterface;

class MessageBus implements MessageBusInterface
{
    public function handle(MessageInterface $message): void
    {
        if ($message instanceof Command) {
            $this->handleCommand($message);

            return;
        }

        if ($message instanceOf Event) {
            $this->handleEvent($message);
        }

        throw new Exception('Unknown message type; expected Command or Event; received: '.get_class($message));
    }

    private function handleCommand(Command $command)
    {
        $command->handle();
    }

    private function handleEvent(Event $command)
    {
        //code
    }
}
