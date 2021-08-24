<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\Relation;

interface HasDocuments
{
    public function documents(): Relation;
}
