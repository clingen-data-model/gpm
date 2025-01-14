<?php
namespace App\Modules\Group\Enums;

enum CurationProduct: string
{
  case Variant = 'variant_pathogenicity';
  case Gene = 'gene_disease_validity';
  case Dosage = 'genetic_dosage_sensitivity';
  case Actionability = 'secondary_findings_actionability';
}