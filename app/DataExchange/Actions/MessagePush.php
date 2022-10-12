<?php

namespace App\DataExchange\Actions;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\DataExchange\Events\Created;
use Lorisleiva\Actions\Concerns\AsJob;
use App\DataExchange\Models\StreamMessage;
use Lorisleiva\Actions\Concerns\AsListener;
use App\DataExchange\Contracts\MessagePusher;
use App\DataExchange\Exceptions\StreamingServiceException;
use App\DataExchange\Exceptions\StreamingServiceDisabledException;

class MessagePush
{
    use AsJob;
    use AsListener;

    private $message;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private MessagePusher $pusher)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(StreamMessage $streamMessage)
    {
        try {

            $this->pusher->topic($streamMessage->topic);

            $this->pusher->push($this->getMessageString($streamMessage->message));

            $streamMessage->update(['sent_at' => Carbon::now()]);

        } catch (StreamingServiceDisabledException $e) {
            if (config('dx.warn-disabled', true)) {
                Log::warning($e->getMessage());
            }
        } catch (StreamingServiceException $e) {
            report($e);
            return;
        }
    }

    public function asJob(StreamMessage $streamMessage)
    {
        $this->handle($streamMessage);
    }

    public function asListener(Created $event): void
    {
        $this->handle($event->streamMessage);
    }


    private function getMessageString($message)
    {
        if (is_string($message)) {
            return $message;
        }

        if (is_array($message) || is_object($message)) {
            return json_encode($message);
        }

        throw new Exception("Expected message to be string, object, or array.  Got ".gettype($message));
    }

}
