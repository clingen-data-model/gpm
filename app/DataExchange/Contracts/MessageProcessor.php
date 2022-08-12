<?php

namespace App\DataExchange\Contracts;

use App\DataExchange\DxMessage;

interface MessageProcessor
{
    public function handle(DxMessage $dxMessage): DxMessage;
}
