<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Models\Document;
use Illuminate\Support\Facades\Event;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\DocumentMarkedFinal;
use Lorisleiva\Actions\Concerns\AsAction;

class ApplicationDocumentMarkFinal
{
    use AsAction;

    public function handle(string $expertPanelUuid, string $documentUuid): Document
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $document = Document::findByUuidOrFail($documentUuid);

        $prevFinal = $expertPanel
            ->documents()
            ->type($document->document_type_id)
            ->final()
            ->get();

        $prevFinal->each(function ($doc) {
            $doc->update(['is_final' => 0]);
        });

        $document->update(['is_final' => 1]);

        Event::dispatch(new DocumentMarkedFinal($expertPanel, $document));

        return $document;
    }
}
