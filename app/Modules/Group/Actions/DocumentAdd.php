<?php

namespace App\Modules\Group\Actions;

use App\Models\Document;
use App\Models\DocumentType;
use App\Modules\Group\Models\Group;
use Lorisleiva\Actions\ActionRequest;
use Illuminate\Support\Facades\Storage;
use App\Modules\Group\Events\DocumentAdded;
use Lorisleiva\Actions\Concerns\AsController;

class DocumentAdd
{
    use AsController;

    public function handle(Group $group, Document $document): Document
    {
        $group->documents()->save($document);

        event(new DocumentAdded($group, $document));
        
        return $document->load('type');
    }

    public function asController(ActionRequest $request, Group $group)
    {
        $file = $request->file('file');

        $path = Storage::disk('local')->putFile('documents', $file);

        $document = new Document([
            'uuid' => $request->uuid,
            'filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'document_type_id' => $request->document_type_id,
            'notes' => $request->notes
        ]);

        return $this->handle($group, $document);
    }

    public function rules(): array
    {
        return [
            'file' => 'required',
            'document_type_id' => 'required|exists:document_types,id',
            'uuid' => 'required'
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'required' => 'This is required.'
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('manageGroupDocuments', $request->group);
    }
}
