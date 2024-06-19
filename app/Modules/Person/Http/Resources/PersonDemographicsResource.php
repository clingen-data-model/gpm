<?php

namespace App\Modules\Person\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\Person\Models\Person;

class PersonDemographicsResource extends JsonResource
{
    public function toArray($request)
    {
        $data = [];
        foreach (Person::$demographics_private_fields as $field) {
            $data[$field] = $this->$field;
        }

        // special case for demographics_completed_date which is not "private"
        $data['demographics_completed_date'] = $this->demographics_completed_date;

        return $data;
    }
}
