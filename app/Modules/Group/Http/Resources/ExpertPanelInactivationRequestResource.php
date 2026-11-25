<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpertPanelInactivationRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid, 'status' => $this->status, 'version' => $this->version,
            'answers' => app(\App\Modules\Group\Actions\InactivationContacts::class)->normalize($this->answers), 'receiving_group_id' => $this->receiving_group_id,
            'submitted_at' => $this->submitted_at, 'approved_at' => $this->approved_at,
            'created_at' => $this->created_at, 'updated_at' => $this->updated_at,
            'can_edit' => $request->user()->can('update', $this->resource),
            'can_delete' => $request->user()->can('delete', $this->resource),
            'upload_max_kb' => \App\Modules\Group\Actions\InactivationUploadLimit::kilobytes(),
            'documents' => $this->documents->map(fn ($doc) => [
                'uuid' => $doc->uuid, 'filename' => $doc->filename, 'download_url' => $doc->download_url,
            ]),
        ];
    }
}
