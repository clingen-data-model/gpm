<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Events\DocumentDeleted;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Lorisleiva\Actions\Concerns\AsAction;

class ApplicationDocumentDelete
{
    use AsAction;

    public function handle(
        string $expertPanelUuid,
        string $documentUuid
    ) {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $document = $expertPanel->group->documents()->where('uuid', $documentUuid)->sole();

        $document->delete();

        event(new DocumentDeleted($expertPanel, $document));
    }
}
