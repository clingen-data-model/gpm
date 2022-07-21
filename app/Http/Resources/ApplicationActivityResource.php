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
        return [
            'id' => $this->id,
            'name' => $this->display_name,
            'uuid' => $this->uuid,
            'current_step' => $this->expertPanel->current_step,
            'type' => $this->type,
            'last_submission_date' => $this->latestSubmission->created_at,
            'submission' => $this->latestSubmission,
        ];
    }
}
