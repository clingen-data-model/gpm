<?php

namespace App\Actions\DataFixes;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsCommand;
/**
 * In the past, it was not uncommon for expert panel applications to be approved without the affiliation_id being set.
 * This makes it difficult for users of the data exchange messages who are expecting an affiliation_id to be present.
 */
class DeleteDuplicateStreamMessages
{
    use AsCommand;

    public string $commandSignature = 'data-fix:backfill-stream-message-affiliation-ids';

    public function handle()
    {
        Log::info('Updating historical stream messages with (current) affiliation_id values.');
        $num_updated = DB::update(<<<EOSQL
        UPDATE
            stream_messages,
            (SELECT sm.id AS id_to_fix, message->>'$.data.expert_panel.id' AS group_uuid, affiliation_id
             FROM stream_messages sm
             JOIN `groups` g ON (message->>'$.data.expert_panel.id' = g.uuid)
             JOIN expert_panels ep ON g.id = ep.group_id where message->>'$.data.expert_panel.affiliation_id' = 'null') subsel
        SET stream_messages.message = JSON_SET(message, '$.data.expert_panel.affiliation_id', affiliation_id)
        WHERE stream_messages.id = id_to_fix;
        EOSQL);
        Log::info('Updated ' . $num_updated . ' stream messages');
    }

}