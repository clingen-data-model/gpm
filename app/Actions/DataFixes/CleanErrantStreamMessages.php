<?php

namespace App\Actions\DataFixes;

use App\DataExchange\Models\StreamMessage;

class CleanErrantStreamMessages
{
    public function handle()
    {
        // Delete errant member_retired for Aurora Pujol on group Peroxisomal Disorders VCEP
        StreamMessage::find(4034)->delete();

        // Delete errant member_removed for Bo Yuan on group SCID VCEP
        StreamMessage::find(2640)->delete();
    }
}