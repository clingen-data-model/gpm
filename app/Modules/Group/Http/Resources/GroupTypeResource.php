<?php

namespace App\Modules\Group\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupTypeResource extends JsonResource
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
        return [
            'id' => $data['id'],
            'name' => $data['name'],
            'fullname' => $data['fullname'],
            'display_name' => $data['display_name'],
            'can_be_parent' => $data['can_be_parent'],
            'curation_product' => $data['curation_product'],
            'is_somatic_cancer' => $data['is_somatic_cancer'],
        ];
    }
}
