<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Jobs\DeleteDocument;
use App\Modules\ExpertPanel\Jobs\MarkDocumentFinal;
use App\Modules\ExpertPanel\Jobs\UpdateDocumentInfo;
use App\Http\Requests\ApplicationDocumentStoreRequest;
use App\Modules\ExpertPanel\Jobs\AddApplicationDocument;
use App\Http\Requests\Applications\DocumentUpdateInfoRequest;
use App\Models\Document;

class ApplicationDocumentController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }
    

    public function store($applicationUuid, ApplicationDocumentStoreRequest $request)
    {
        $application = ExpertPanel::findByUuidOrFail($applicationUuid);

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

        $command = new AddApplicationDocument($applicationUuid, ...$data);
        $this->dispatcher->dispatch($command);

        $newDocument = Document::findByUuid($request->uuid);

        return $newDocument->toArray();
    }

    public function update($appUuid, $docUuid, DocumentUpdateInfoRequest $request)
    {
        $command = new UpdateDocumentInfo(
            applicationUuid: $appUuid,
            uuid: $docUuid,
            dateReceived: $request->date_received,
            notes: $request->notes
        );
        $this->dispatcher->dispatch($command);
        
        $document = Document::findByUuidOrFail($docUuid);


        return response($document, 200);
    }

    public function markFinal($appUuid, $docUuid)
    {
        $job = new MarkDocumentFinal($appUuid, $docUuid);
        $this->dispatcher->dispatch($job);

        $updatedDocument = Document::findByUuid($docUuid);

        return $updatedDocument;
    }

    public function destroy($appUuid, $docUuid)
    {
        $job = new DeleteDocument($appUuid, $docUuid);
        $this->dispatcher->dispatch($job);
    }
}
