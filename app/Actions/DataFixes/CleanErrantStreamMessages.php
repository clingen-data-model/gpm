<?php

namespace App\Actions\DataFixes;

use App\DataExchange\Models\StreamMessage;

class CleanErrantStreamMessages
{
    public function handle()
    {
        
        // Delete errant member_retired for Aurora Pujol on group Peroxisomal Disorders VCEP
        $aPujolMemberRetired = StreamMessage::where([
            'message->event_type' => 'member_retired',
            'message->data->expert_panel->affiliation_id' => '50049',
            'message->data->members[0]->email' => 'apujol@IDIBELL.CAT',
        ])->first();

        if ($aPujolMemberRetired) {
            $aPujolMemberRetired->delete();
        }


        // Delete errant member_removed for Bo Yuan on group SCID VCEP
        $bYuanMemberRemoved = StreamMessage::where([
            'message->event_type' => 'member_removed',
            'message->data->expert_panel->affiliation_id' => '50091',
            'message->data->members[0]->email' => 'by2@bcm.edu',
        ])->first();

        if ($bYuanMemberRemoved) {
            $bYuanMemberRemoved->delete();
        }
    }
}