<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $submission = $this->activeScopeOfWorkSubmission ?? $this->latestSubmission;
        return [
            'id' => $this->id,
            'name' => $this->display_name,
            'uuid' => $this->uuid,
            'current_step' => $this->expertPanel->current_step,
            'type' => $this->type,
            'last_submission_date' => $submission->created_at,
            'submitted_step' => $this->activeScopeOfWorkSubmission
                ? 'Scope of Work Revision — '.$submission->type->name
                : $submission->type->name,
            'submission' => $submission,
        ];
    }
}
