<?php

namespace App\Modules\ExpertPanel;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use InvalidArgumentException;

class CoiDataCaster implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): CoiData
    {
        return new CoiData(json_decode($value));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        // if (! $value instanceof CoiData) {
        //     throw new InvalidArgumentException('The given value is not an CoiData object.');
        // }
        return $value->toJson();
    }
}
