<?php

namespace App\DataExchange\Actions;

use Carbon\Carbon;
use App\DataExchange\DxMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\DataExchange\MessageHandlerFactory;
use App\DataExchange\Contracts\MessageProcessor;
use App\DataExchange\Models\IncomingStreamMessage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\DataExchange\Exceptions\UnsupportedIncomingMessage;
use Lorisleiva\Actions\Concerns\AsJob;

class IncomingMessageProcess implements MessageProcessor
{
    use AsJob;

    public function __construct(
        private IncomingMessageStore $storeMessage,
        private MessageHandlerFactory $handlerFactory
    ) {
        //code
    }


    public function handle(DxMessage $message): DxMessage
    {
        return DB::transaction(function () use ($message) {
            // Store the message and as an IncomingStreamMessage
            $incomingStreamMessage = $this->storeMessage->handle($message);

            try {
                // Insantiate the action for the type and run the handler
                $this->makeHandlerForMessage($incomingStreamMessage)
                    ->handle($incomingStreamMessage);

                // Mark the incomingStreamMessageProcessed
                $incomingStreamMessage->update(['processed_at' => Carbon::now()]);

            } catch (ModelNotFoundException $e) {
                Log::error('Received "classified-rules-approved" event from CSPEC for expert panel with affiliation_id '.$message->payload->affiliationId.', but not found.');
            } catch (UnsupportedIncomingMessage $e) {
                Log::error($e->getMessage());
            }

            // return the original message
            return $message;
        });
    }

    private function makeHandlerForMessage($message)
    {
        return $this->handlerFactory->make($message);
    }

}
