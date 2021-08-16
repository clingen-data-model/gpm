<?php
namespace App\Modules\ExpertPanel\Actions;

use App\Models\Document;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\DocumentDeleted;

class ApplicationDocumentDelete
{
    use AsAction;

    public function handle(
        string $expertPanelUuid,
        string $documentUuid
    ) {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $document = $expertPanel->documents()->where('uuid', $documentUuid)->sole();

        $document->delete();

        event(new DocumentDeleted($expertPanel, $document));
    }
}
