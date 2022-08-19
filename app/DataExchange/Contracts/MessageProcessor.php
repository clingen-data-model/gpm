<?php

namespace App\DataExchange\Contracts;

use App\DataExchange\DxMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

interface MessageProcessor extends ShouldQueue
{
    public function handle(DxMessage $dxMessage): DxMessage;
    static public function makeJob(): ShouldQueue;
}
