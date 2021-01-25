<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Bus\Dispatcher;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Event;
use App\Domain\Application\Models\Application;
use App\Http\Requests\ApplicationDocumentStoreRequest;
use App\Domain\Application\Jobs\AddApplicationDocument;

class ApplicationDocumentController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }
    

    public function store($applicationUuid, ApplicationDocumentStoreRequest $request)
    {
        $application = Application::findByUuidOrFail($applicationUuid);

        $path = $request->file('file')->store('documents');
        $file = $request->file('file');

        $data = $request->only([
            'uuid', 
            'document_category_id', 
            'date_received', 
            'date_reviewed', 
            'metadata', 
            'version',
        ]);

        $data['storage_path'] = $path;
        $data['filename'] = $file->name;

        $command = new AddApplicationDocument($application, ...$data);
        $this->dispatcher->dispatch($command);

        $newDocument = Document::findByUuid($request->uuid);

        return $newDocument;
    }
    
}
