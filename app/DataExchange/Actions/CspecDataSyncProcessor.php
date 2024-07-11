<?php

namespace App\DataExchange\Actions;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsJob;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\DataExchange\Models\IncomingStreamMessage;
use App\Modules\ExpertPanel\Actions\SpecificationAndRulesetsSync;

class CspecDataSyncProcessor
{
    use AsJob;

    public function __construct(private SpecificationAndRulesetsSync $syncSpecificationAndRulesets)
    {
    }


    public function handle(IncomingStreamMessage $message)
    {
        $cspecDoc = $message->payload->cspecDoc;

        $expertPanel = ExpertPanel::findByAffiliationId($cspecDoc->affiliationId);
        if (!$expertPanel) {
            Log::error('Received proposal-submitted event about EP with unknown affiliation id '.$cspecDoc->affiliationId);
            return;
        }

        $this->syncSpecificationAndRulesets->handle(
            cspecId: $cspecDoc->cspecId,
            expertPanel: $expertPanel,
            name: $cspecDoc->name,
            status: $cspecDoc->status,
            rulesets: $cspecDoc->ruleSets
        );
    }
}
