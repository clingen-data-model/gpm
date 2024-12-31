<?php
namespace App\Services;

interface DiseaseLookupInterface
{
    public function findNameByOntologyId(string $ontologyId): string;
}