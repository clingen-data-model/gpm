<?php

namespace App\Models\Traits;

interface Snapshotable
{
    public function createSnapshot(): array;

    public static function initFromSnapshot($snapshot): self;
}
