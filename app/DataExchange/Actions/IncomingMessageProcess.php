<?php

namespace App\DataExchange\Actions;

use App\DataExchange\Exceptions\UnsupportedIncomingMessage;

class IncomingMessageProcess
{
    public function __construct(private IncomingMessageStore $storeMessage, private MessageHandlerFactory $handlerFactory)
    {
        //code
    }
    

    public function handle($argument)
    {
        // Store message in the database
        try {
            // Insantiate the action for the type
            // Run the type action handler
            // Mark the incomingStreamMessageProcessed
        } catch (UnsupportedIncomingMessage $e) {
            report($e->getMessage());
        }
    }
}
