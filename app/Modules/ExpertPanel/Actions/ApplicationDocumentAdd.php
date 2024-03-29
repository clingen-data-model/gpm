<?php

namespace App\Modules\ExpertPanel\Actions;

use Carbon\Carbon;
use App\Models\Document;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\DocumentAdded;
use App\Modules\ExpertPanel\Http\Requests\ApplicationDocumentStoreRequest;

class ApplicationDocumentAdd
{
    use AsAction;
    
    public function handle(
        string $expertPanelUuid,
        string $uuid,
        string $filename,
        string $storage_path,
        int $document_type_id,
        ?int $step = null,
        ?string $date_received = null,
        ?array $metadata = [],
        ?bool $is_final = false,
        ?string $notes = null
    ): Document {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $dateReceived = $date_received ? Carbon::parse($date_received) : Carbon::now();

        $document = new Document([
            'uuid' => $uuid,
            'filename' => $filename,
            'storage_path' => $storage_path,
            'document_type_id' => $document_type_id,
            'metadata' => array_merge($metadata, [
                'step' => $step,
                'is_final' => $is_final,
                'date_received' => $dateReceived,
            ]),
            'notes' => $notes
        ]);
        
        $document->version = 1 + $expertPanel->getLatestVersionForDocument($document->document_type_id);

        $expertPanel->group->documents()->save($document);

        $this->updateOrTouchExpertPanel($expertPanel, $document);

        $event = new DocumentAdded(
            application: $expertPanel,
            document: $document
        );
        Event::dispatch($event);

        return $document;
    }

    public function asController($expertPanelUuid, ApplicationDocumentStoreRequest $request)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);

        $file = $request->file('file');

        $path = Storage::disk('local')->putFile('documents', $file);

        $data = $request->only([
            'uuid',
            'document_type_id',
            'date_received',
            'metadata',
            'step',
            'is_final',
            'notes'
        ]);

        $data['storage_path'] = $path;
        $data['filename'] = $file->getClientOriginalName();
        $data['expertPanelUuid'] = $expertPanelUuid;

        $newDocument = $this->handle(...$data);

        return $newDocument->toArray();
    }

    private function updateOrTouchExpertPanel($expertPanel, $document): void
    {
        if ($document->version == 1) {
            if ($document->document_type_id == config('documents.types.scope.id')) {
                $expertPanel->step_1_received_date = $document->dateReceived;
            }
            if ($document->document_type_id == config('documents.types.final-app.id')) {
                $expertPanel->step_4_received_date = $document->dateReceived;
            }
            $expertPanel->save();
            return;
        }

        $expertPanel->touch();
    }
}
