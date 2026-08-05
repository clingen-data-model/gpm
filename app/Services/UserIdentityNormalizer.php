<?php

namespace App\Services;

class UserIdentityNormalizer
{
    public static function normalizeEmail(?string $email): ?string
    {
        if (!$email) { return null; }
        $email = trim(mb_strtolower($email));
        return $email === '' ? null : $email;
    }

    public static function normalizeNamePart(?string $name): ?string
    {
        $name = trim((string) $name);
        return $name === '' ? null : $name;
    }

    public static function normalizeName(?string $name): ?string
    {
        $name = trim((string) $name);
        $name = preg_replace('/\s+/u', ' ', $name);
        return $name === '' ? null : $name;
    }

    public static function splitName(?string $fullName): array
    {
        $fullName = self::normalizeName($fullName);

        if (!$fullName) { return [null, null]; }
        $parts = preg_split('/\s+/u', $fullName, 2);
        return [
            self::normalizeNamePart($parts[0] ?? null),
            self::normalizeNamePart($parts[1] ?? null),
        ];
    }

    private function normalizeString($value): ?string
    {
        if ($value === null) return null;
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}