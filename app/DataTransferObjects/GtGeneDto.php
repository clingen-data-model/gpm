<?php 

namespace App\DataTransferObjects;

class GtGeneDto
{
    public function __construct(        
        public int $hgnc_id,        
        public string $gene_symbol,
        public int $omim_id,
        public string $hgnc_name,
        public string $hgnc_status
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            hgnc_id     : $data['hgnc_id'],
            gene_symbol : $data['gene_symbol'],
            omim_id     : $data['omim_id'],
            hgnc_name   : $data['hgnc_name'],
            hgnc_status : $data['hgnc_status']

        );
    }
}
