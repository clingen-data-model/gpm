<?php

namespace App\Modules\Group\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Group\Actions\{InactivationAnswersValidate, InactivationRequestStart, InactivationContacts, InactivationPublications, InactivationUploadLimit};
use App\Modules\Group\Http\Resources\ExpertPanelInactivationRequestResource;
use App\Modules\Group\Models\{Group, ExpertPanelInactivationRequest};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class InactivationRequestController extends Controller
{
    public function show(Request $request, Group $group)
    {
        $this->authorize('viewGroup', [ExpertPanelInactivationRequest::class, $group]);
        $item = ExpertPanelInactivationRequest::where('group_id', $group->id)->first();
        return response()->json([
            'data' => $item ? (new ExpertPanelInactivationRequestResource($item))->resolve($request) : null,
            'can_start' => $request->user()->can('create', [ExpertPanelInactivationRequest::class, $group]),
        ]);
    }

    public function store(Request $request, Group $group, InactivationRequestStart $action)
    {
        $this->authorize('create', [ExpertPanelInactivationRequest::class, $group]);
        return new ExpertPanelInactivationRequestResource($action->handle($group, $request->user()));
    }

    public function update(Request $request, Group $group, InactivationAnswersValidate $validate)
    {
        return $this->mutate($request, $group, function ($item) use ($request, $group, $validate) {
            $request->validate(['answers' => 'required|array']);
            $answers = app(InactivationPublications::class)->merge($group, $request->input('answers'), $item->answers);
            $item->answers = $validate->handle($group, $answers, false, $item->answers);
            $item->schema_version = 3;
            $item->receiving_group_id = ($item->answers['transfer_planned'] ?? null) === 'yes' ? ($item->answers['receiving_group_id'] ?? null) : null;
        });
    }

    public function submit(Request $request, Group $group, InactivationAnswersValidate $validate)
    {
        $changed = false;
        $result = $this->mutate($request, $group, function ($item) use ($request, $group, $validate, &$changed) {
            $publications = app(InactivationPublications::class);
            $answers = $publications->merge($group, $item->answers, $item->answers);
            $changed = $publications->signature($item->answers['publications'] ?? []) !== $publications->signature($answers['publications']);
            if ($changed) { $item->answers = $answers; return; }
            $item->answers = $validate->handle($group, $answers, true, $item->answers);
            $item->schema_version = 3;
            if (!$item->documents()->exists()) throw ValidationException::withMessages(['final_report' => 'Upload a final curation report before submitting.']);
            $item->status = ExpertPanelInactivationRequest::SUBMITTED;
            $item->review_stage = 'core';
            $item->submitted_at = now();
            $item->submitted_by_id = $request->user()->id;
        });
        if ($changed) return response()->json(['data' => $result->resolve($request), 'errors' => ['publications_acknowledged' => ['The publication list changed. Review it and acknowledge it again before submitting.']]], 422);
        return $result;
    }

    public function refreshPublications(Request $request, Group $group)
    {
        return $this->mutate($request, $group, function ($item) use ($request, $group) {
            $input = $request->validate([
                'publications' => 'present|array',
                'publications.*.publication_id' => 'nullable|string',
                'publications.*.pmid' => 'nullable|string',
                'publications.*.pmcid' => 'nullable|string',
                'publications.*.title' => 'nullable|string',
                'publications.*.pmc_compliant' => 'nullable|in:yes,no,unknown',
                'publications_acknowledged' => 'nullable|boolean',
            ]);
            $answers = $item->answers;
            $answers['publications'] = $input['publications'];
            $answers['publications_acknowledged'] = $input['publications_acknowledged'] ?? false;
            $item->answers = app(InactivationPublications::class)->merge($group, $answers, $item->answers);
        });
    }

    public function upload(Request $request, Group $group)
    {
        $path = null;
        try {
            return $this->mutate($request, $group, function ($item) use ($request, &$path) {
                $request->validate(['file' => 'required|file|max:'.InactivationUploadLimit::kilobytes().'|mimes:pdf,csv,txt,xls,xlsx,doc,docx']);
                $file = $request->file('file');
                $path = Storage::disk('local')->putFile('documents/inactivation', $file);
                abort_unless($path, 500, 'Could not store the report.');
                $item->documents()->create([
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'filename' => $file->getClientOriginalName(), 'storage_path' => $path,
                    'document_type_id' => config('documents.types.miscellaneous.id'),
                    'metadata' => ['purpose' => 'final-curation-report'],
                ]);
            });
        } catch (\Throwable $exception) {
            if ($path) Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function deleteDocument(Request $request, Group $group, string $documentUuid)
    {
        return $this->mutate($request, $group, function ($item) use ($documentUuid) {
            $item->documents()->where('uuid', $documentUuid)->firstOrFail()->delete();
        });
    }

    public function destroy(Request $request, Group $group)
    {
        DB::transaction(function () use ($request, $group) {
            // Use the creation lock as well as the mutation lock to serialize replacement drafts.
            Group::whereKey($group->id)->lockForUpdate()->firstOrFail();
            $item = ExpertPanelInactivationRequest::where('group_id', $group->id)->lockForUpdate()->firstOrFail();
            $this->authorize('delete', $item);
            $data = $request->validate(['uuid' => 'required|uuid', 'version' => 'required|integer|min:1']);
            abort_if($data['uuid'] !== $item->uuid || (int) $data['version'] !== $item->version, 409, 'This request changed. Reload it before deleting.');

            // Include soft-deleted reports: removing a report from a draft retains its file.
            $documents = $item->documents()->withTrashed()->get();
            $disk = Storage::disk('local');
            foreach ($documents as $document) {
                if ($disk->exists($document->storage_path)) {
                    abort_unless($disk->delete($document->storage_path), 500, 'Could not remove an uploaded document. Please retry deleting the draft.');
                }
            }
            foreach ($documents as $document) $document->forceDelete();
            $item->delete();
        });

        return response()->json(['can_start' => $request->user()->can('create', [ExpertPanelInactivationRequest::class, $group])]);
    }

    private function mutate(Request $request, Group $group, callable $change)
    {
        return DB::transaction(function () use ($request, $group, $change) {
            $item = ExpertPanelInactivationRequest::where('group_id', $group->id)->lockForUpdate()->firstOrFail();
            $this->authorize('update', $item);
            $request->validate(['version' => 'required|integer|min:1']);
            abort_if((int) $request->input('version') !== $item->version, 409, 'This request changed. Reload it before saving.');
            $change($item);
            $item->version++;
            $item->save();
            return new ExpertPanelInactivationRequestResource($item->fresh());
        });
    }

    public function options(Request $request, Group $group, InactivationContacts $contacts)
    {
        $this->authorize('create', [ExpertPanelInactivationRequest::class, $group]);
        $types = $group->is_gcep ? [config('groups.types.gcep.id')] : [config('groups.types.vcep.id'), config('groups.types.scvcep.id')];
        $receivers = Group::whereIn('group_type_id', $types)->whereKeyNot($group->id)
            ->where('group_status_id', config('groups.statuses.active.id'))->with('parent')->orderBy('name')->get();
        $selectedMembers = [];
        $suggestions = [];
        if ($request->filled('receiving_group_id')) {
            $receiver = $receivers->firstWhere('id', (int) $request->receiving_group_id);
            abort_unless($receiver, 422, 'Select an eligible receiving panel.');
            $selectedMembers = $contacts->members($receiver);
            $suggestions = $contacts->defaults($receiver);
        }
        return response()->json([
            'members' => $contacts->members($group), 'receiving_members' => $selectedMembers,
            'receiving_liaison_suggestions' => $suggestions,
            'receiving_groups' => $receivers->map(fn ($g) => [
                'id' => $g->id, 'name' => $g->display_name, 'affiliation_id' => (string) $g->affiliation_id,
                'parent_name' => $g->parent?->display_name,
            ]),
        ]);
    }
}
