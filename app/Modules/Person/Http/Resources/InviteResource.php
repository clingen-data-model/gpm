<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InviteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        if ($this->relationLoaded('inviter')) {
            $data['coi_code'] = $this->coi_code;
        }
        return $data;
    }
}
