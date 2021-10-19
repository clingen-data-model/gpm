<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
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
        $data = $this->unsetKeys([
            'created_at',
            'updated_at',
            'guard_name',
            'pivot',
            'scope'
        ], $data);
        return $data;
    }

    public function unsetKeys($keys, $data)
    {
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }
        return $data;
    }
}
