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
            'group' => [
                'id' => $this->group->id,
                'name' => $this->group->display_name,
                'uuid' => $this->group->uuid,
                'current_step' => $this->group->expertPanel->current_step,
                'type' => $this->group->type,
                'last_submission_date' => $this->group->latestSubmission->created_at
            ],
            'submission' => [
                'id' => $this->id,
                'type' => $this->type,
                'status' => $this->status,
                'notes' => $this->notes,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]
        ];
    }
}
