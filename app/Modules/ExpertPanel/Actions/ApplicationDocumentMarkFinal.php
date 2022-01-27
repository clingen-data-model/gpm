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

        $prevFinal = $expertPanel->group
            ->documents()
            ->type($document->document_type_id)
            ->final()
            ->get();
        
        $prevFinal->each(function ($doc) {
            $doc->is_final = 0;
            $doc->save();
        });

        $document->isFinal = 1;
        $document->save();


        Event::dispatch(new DocumentMarkedFinal($expertPanel, $document));

        return $document;
    }
}
