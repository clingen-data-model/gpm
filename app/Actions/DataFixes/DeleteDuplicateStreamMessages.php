<?php

namespace App\Actions\DataFixes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsCommand;
/**
 * It is not clear to me why there are some duplicated entries in the stream_messages table.
 * Maybe this happens when there is an error sending the message and it is retried?
 * For now, I will delete all the duplicated entries in the stream_messages table (in preparation
 * for possible re-sending after we have re-written some messages -bpow)
 */
class DeleteDuplicateStreamMessages
{
    use AsCommand;

    public string $commandSignature = 'data-fix:delete-duplicate-stream-messages';

    public function handle()
    {
        Log::info('Deleting duplicate stream messages');
        $num_deleted = DB::delete('
        DELETE FROM stream_messages
        WHERE id NOT IN ( 
            SELECT min_id
            FROM (
                SELECT MIN(id) AS min_id
                FROM stream_messages
                GROUP BY topic, message, error
            ) unique_rows);
        ');
        Log::info('Deleted ' . $num_deleted . ' duplicate stream messages');
    }

}