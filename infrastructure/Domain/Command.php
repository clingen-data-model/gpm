<?php

namespace Infrastructure\Domain;

abstract class Command extends Message
{
    abstract public function handle():void;    
}
