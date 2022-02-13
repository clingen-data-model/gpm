<?php

namespace App\Modules\ExpertPanel;

use InvalidArgumentException;
use App\Modules\ExpertPanel\CoiData;
use PhpParser\Node\Expr\Cast\String_;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class CoiDataCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes): CoiData
    {
        return new CoiData(json_decode($value));
    }

    public function set($model, $key, $value, $attributes): String
    {
        if (! $value instanceof CoiData) {
            throw new InvalidArgumentException('The given value is not an CoiData object.');
        }
        return $value->toJson();
    }
}
