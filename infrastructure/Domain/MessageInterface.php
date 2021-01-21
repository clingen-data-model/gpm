<?php

namespace Infrastructure\Domain;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

interface MessageInterface extends JsonSerializable, Arrayable
{
    public function equals(MessageInterface $other): bool;
}
