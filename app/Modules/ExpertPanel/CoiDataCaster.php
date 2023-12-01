<?php

namespace App\Modules\ExpertPanel;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class CoiDataCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): CoiData
    {
        return new CoiData(json_decode($value));
    }

    public function set($model, $key, $value, $attributes): string
    {
        // if (! $value instanceof CoiData) {
        //     throw new InvalidArgumentException('The given value is not an CoiData object.');
        // }
        return $value->toJson();
    }
}
