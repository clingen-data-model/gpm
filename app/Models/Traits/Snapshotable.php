<?php

namespace App\Models\Traits;

interface Snapshotable
{
    public function createSnapshot(): Array;

    static public function initFromSnapshot($snapshot): Self;
}