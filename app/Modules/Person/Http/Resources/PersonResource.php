<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $data['institution'] = $this->whenLoaded('institution', $this->institution);

        return $data;
    }
}
