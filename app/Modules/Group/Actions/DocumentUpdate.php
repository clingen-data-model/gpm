<?php

namespace App\Modules\Group\Actions;

use App\Models\Document;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\Group\Events\DocumentUpdated;

class DocumentUpdate
{
    use AsAction;

    public function handle(Group $group, Document $document, array $data): Document
    {
        $document->fill($data);

        if ($document->isDirty()) {
            DB::transaction(function () use ($document, $group) {
                $document->save();
                Event::dispatch(new DocumentUpdated($group, $document));
            });
        }

        return $document->load('type');
    }
    
    public function asController(ActionRequest $request, Group $group, Document $document)
    {
        return $this->handle($group, $document, $request->only(['filename', 'document_type_id', 'notes']));
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('manageGroupDocuments', $request->group);
    }

    public function rules(): array
    {
        return [
            'document_type_id' => 'required|exists:document_types,id'
        ];
    }

    public function getValidationMessages(): array
    {
        return [
            'required' => 'This is required.',
            'exists' => 'The selected type is invalid.'
        ];
    }
}
