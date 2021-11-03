<?php
namespace App\Services;

interface MondoLookupInterface
{
    public function findNameByMondoId($mondoId): string;
}