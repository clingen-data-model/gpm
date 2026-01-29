<?php
namespace App\Modules\ExpertPanel\Enums;

enum AffiliationType: string
{
    case GCEP   = 'GCEP';
    case VCEP   = 'VCEP';
    case SC_VCEP= 'SC_VCEP';

    public static function fromExpertPanelLabel(?string $label): self
    {
        $u = strtoupper(trim($label ?? ''));
        return match ($u) {
            'VCEP'   => self::VCEP,
            'SCVCEP' => self::SC_VCEP,
            default  => self::GCEP,
        };
    }

    /** For validation rules (Rule::in()) */
    public static function values(): array
    {
        return array_map(fn(self $c) => $c->value, self::cases());
    }
}
