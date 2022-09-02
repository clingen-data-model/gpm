<?php

namespace App\DataExchange\Actions;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsJob;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\RulesetStatus;
use App\DataExchange\Models\IncomingStreamMessage;
use App\Modules\ExpertPanel\Actions\SpecificationSync;
use App\Modules\ExpertPanel\Models\SpecificationStatus;
use App\Modules\ExpertPanel\Actions\SpecificationCreate;
use App\Modules\ExpertPanel\Actions\SpecificationRulesetCreate;
use App\Modules\ExpertPanel\Actions\SpecificationAndRulsetsSync;

class CspecDataSyncProcessor
{
    use AsJob;

    public function __construct(private SpecificationAndRulsetsSync $syncSpecification)
    {
    }


    public function handle(IncomingStreamMessage $message)
    {
        $cspecDoc = $message->payload->cspecDoc;

        $expertPanel = ExpertPanel::findByAffiliationId($cspecDoc->affiliationId);
        if (!$expertPanel) {
            Log::error('Received proposal-submitted event about EP with unkown affiliation id '.$cspecDoc->affiliationId);
            return;
        }

        $this->syncSpecification->handle(
            cspecId: $cspecDoc->cspecId,
            expertPanel: $expertPanel,
            name: $cspecDoc->name,
            event: $cspecDoc->status->event,
            rulesets: $cspecDoc->ruleSets
        );
    }

}
