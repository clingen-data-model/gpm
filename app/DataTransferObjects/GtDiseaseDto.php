<?php

namespace App\DataTransferObjects;

class GtDiseaseDto
{
    public function __construct(
        public string $mondo_id,
        public string $doid_id,
        public string $name,
        public string $is_absolute,
        public string $replaced_by,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(            
            mondo_id    : $data['mondo_id'],
            doid_id     : $data['doid_id'],
            name        : $data['name'],
            is_absolute : $data['is_absolute'],
            replaced_by : $data['replaced_by'],
        );
    }
}
