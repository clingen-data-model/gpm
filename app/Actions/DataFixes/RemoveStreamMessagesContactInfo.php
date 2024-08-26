<?php

namespace App\Actions\DataFixes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsCommand;
/**
 * Contact information (address, phone number) is stored in the GPM so coordinators
 * can get to it, but only the email address should be provided to the data exchange.
 *
 * Old messages had these fields in the data exchange, so we need to remove them in
 * preparation for re-writing to the message queue.
 */
class RemoveStreamMessagesContactInfo
{
    use AsCommand;

    public string $commandSignature = 'data-fix:remove-address-phone-from-stream-messages';

    public function handle()
    {
        Log::info('Removing address and phone from data exchange gpm-person-general message logs');
        $num_affected = DB::update("
        UPDATE stream_messages
        SET message = JSON_REMOVE(message, '$.data.person.address')
        WHERE topic = 'gpm-person-events';
        ");
        Log::info('Removed address from ' . $num_affected . ' data exchange gpm-person-general message logs');

        $num_affected = DB::update("
        UPDATE stream_messages
        SET message = JSON_REMOVE(message, '$.data.person.phone')
        WHERE topic = 'gpm-person-events';
        ");
        Log::info('Removed phone from ' . $num_affected . ' data exchange gpm-person-general message logs');
    }
}
