<?php

namespace App\DataTransferObjects;

class GtDiseaseDto
{
    public function __construct(
        public string $mondo_id,
        public ?string $doid_id = null,
        public string $name,
        public ?string $is_obsolete = null,
        public ?string $replaced_by = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(            
            mondo_id    : $data['mondo_id'],
            doid_id     : $data['doid_id'] ?? null,
            name        : $data['name'],
            is_obsolete : $data['is_obsolete'] ?? null,
            replaced_by : $data['replaced_by'] ?? null,
        );
    }
}