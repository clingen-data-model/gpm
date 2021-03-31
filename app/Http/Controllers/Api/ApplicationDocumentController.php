<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Jobs\MarkDocumentFinal;
use App\Http\Requests\ApplicationDocumentStoreRequest;
use App\Modules\Application\Jobs\MarkDocumentReviewed;
use App\Modules\Application\Jobs\AddApplicationDocument;
use App\Http\Requests\Applications\MarkDocumentReviewedRequest;

class ApplicationDocumentController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }
    

    public function store($applicationUuid, ApplicationDocumentStoreRequest $request)
    {
        $application = Application::findByUuidOrFail($applicationUuid);

        $file = $request->file('file');

        $path = Storage::disk('local')->putFile('documents', $file);

        $data = $request->only([
            'uuid', 
            'document_type_id', 
            'date_received', 
            'date_reviewed', 
            'metadata', 
            'step',
            'is_final'
        ]);

        $data['storage_path'] = $path;
        $data['filename'] = $file->getClientOriginalName();

        $command = new AddApplicationDocument($applicationUuid, ...$data);
        $this->dispatcher->dispatch($command);

        $newDocument = Document::findByUuid($request->uuid);

        return $newDocument->toArray();
    }

    public function markReviewed($appUuid, $docUuid, MarkDocumentReviewedRequest $request)
    {
        $job = new MarkDocumentReviewed($appUuid, $docUuid, Carbon::parse($request->date_reviewed));
        $this->dispatcher->dispatch($job);

        $updatedDocument = Document::findByUuid($docUuid);

        return $updatedDocument;
    }

    public function markFinal($appUuid, $docUuid)
    {
        $job = new MarkDocumentFinal($appUuid, $docUuid);
        $this->dispatcher->dispatch($job);

        $updatedDocument = Document::findByUuid($docUuid);

        return $updatedDocument;
    }
    
}
