<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Models\Document;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\DocumentInfoUpdated;
use App\Modules\ExpertPanel\Http\Requests\DocumentUpdateInfoRequest;

class UpdateApplicationDocument
{
    use AsAction;

    public function handle(
        String $expertPanelUuid,
        String $uuid,
        String $dateReceived,
        string|null $notes = null
    ): Document {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $document = Document::findByUuidOrFail($uuid);

        $document->date_received = $dateReceived;
        $document->notes = $notes;

        if ($document->isDirty()) {
            DB::transaction(function () use ($document, $expertPanel) {
                $document->save();
                Event::dispatch(new DocumentInfoUpdated($expertPanel, $document));
            });
        }

        return $document;
    }
    
    public function asController($appUuid, $docUuid, DocumentUpdateInfoRequest $request): Response
    {
        $document = $this->handle(
            expertPanelUuid: $appUuid,
            uuid: $docUuid,
            dateReceived: $request->date_received,
            notes: $request->notes
        );

        return response($document, 200);

    }
    
}