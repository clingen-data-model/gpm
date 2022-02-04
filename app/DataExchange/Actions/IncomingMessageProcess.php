<?php

namespace App\DataExchange\Actions;

use Carbon\Carbon;
use App\DataExchange\DxMessage;
use Illuminate\Support\Facades\Log;
use App\DataExchange\MessageHandlerFactory;
use App\DataExchange\Models\IncomingStreamMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\DataExchange\Exceptions\UnsupportedIncomingMessage;

class IncomingMessageProcess
{
    public function __construct(
        private IncomingMessageStore $storeMessage,
        private MessageHandlerFactory $handlerFactory
    ) {
        //code
    }
    

    public function handle(DxMessage $message)
    {
        $incomingStreamMessage = $this->storeMessage->handle($message);

        try {
            // Insantiate the action for the type
            $action = $this->handlerFactory->make($incomingStreamMessage);
            
            // Run the type action handler
            $message = $action->handle($incomingStreamMessage);
            
            // Mark the incomingStreamMessageProcessed
            $message->update(['processed_at' => Carbon::now()]);

            return $message;
        } catch (ModelNotFoundException $e) {
            Log::error('Received "classified-rules-approved" event from CSPEC for expert panel with affiliation_id '.$message->payload->affiliationId.', but not found.');
        } catch (UnsupportedIncomingMessage $e) {
            Log::error($e->getMessage());
        }
    }
}
