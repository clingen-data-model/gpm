<?php

namespace App\Auth\CILogon;

use RuntimeException;

class CILogonLoginRejected extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
