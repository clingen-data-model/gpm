<?php

namespace App\Modules\Group\Service;

class PublicationLookup
{
    public static function normalize(string $raw): array
    {
        $s = trim($raw);

        if (preg_match('/^pmid:\s*(\d+)$/i', $s, $m) || ctype_digit($s)) {
            return ['pmid', $m[1] ?? $s];
        }
        if (preg_match('/^pmcid:\s*(PMC\d+)$/i', $s, $m)) {
            return ['pmcid', strtoupper($m[1])];
        }
        if (preg_match('~10\.\d{4,9}/\S+~', $s, $m)) {
            return ['doi', $m[0]];
        }
        if (preg_match('~^https?://~i', $s)) {
            return ['url', $s];
        }

        return ['url', $s];
    }
}
