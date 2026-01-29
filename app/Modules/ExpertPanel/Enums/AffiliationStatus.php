<?php
namespace App\Modules\ExpertPanel\Enums;

enum AffiliationStatus: string
{
    case APPLYING = 'APPLYING';
    case ACTIVE   = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case ARCHIVED = 'ARCHIVED';
    case RETIRED  = 'RETIRED';

    /** Map GPM group.status.name -> AM status (strings differ in case & set) */
    public static function fromGroupStatusName(?string $name): self
    {
        $u = strtoupper(trim($name ?? ''));
        return match ($u) {
            'APPLYING' => self::APPLYING,
            'ACTIVE'   => self::ACTIVE,
            'INACTIVE' => self::INACTIVE,
            'RETired'  => self::RETIRED,
            'REMOVED'  => self::ARCHIVED,   // AM uses ARCHIVED, not REMOVED
            'ARCHIVED' => self::ARCHIVED,
            default    => self::INACTIVE,
        };
    }

    public static function values(): array
    {
        return array_map(fn(self $c) => $c->value, self::cases());
    }
}
