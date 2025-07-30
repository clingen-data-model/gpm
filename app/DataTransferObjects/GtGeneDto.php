<?php 

namespace App\DataTransferObjects;

class GtGeneDto
{
    public function __construct(        
        public int $hgnc_id,        
        public string $gene_symbol,
        public ?int $omim_id = null,
        public ?string $hgnc_name = null,
        public ?string $hgnc_status = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            hgnc_id     : $data['hgnc_id'],
            gene_symbol : $data['gene_symbol'],
            omim_id     : $data['omim_id'] ?? null,
            hgnc_name   : $data['hgnc_name'] ?? null,
            hgnc_status : $data['hgnc_status'] ?? null

        );
    }
}