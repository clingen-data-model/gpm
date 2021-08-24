<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\Relation;

interface HasMembers
{
    public function members():Relation;
    public function chairs(): Relation;
    public function coordinators(): Relation;
}
