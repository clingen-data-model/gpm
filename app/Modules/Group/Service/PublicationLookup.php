<?php

namespace App\Modules\Group\Service;

class PublicationLookup
{
    public static function normalize(string $raw): array
    {
        $s = trim($raw);

        if (preg_match('/^(?:pmid:)?\s*(\d+)$/i', $s, $m)) {
            return ['pmid', $m[1]];
        }

        if (preg_match('/^(?:pmcid:)?\s*(pmc)?\s*(\d+)$/i', $s, $m)) {
            return ['pmcid', 'PMC'.strtoupper($m[2])];
        }

        if (preg_match('~(10\.\d{4,9}/\S+)~i', $s, $m)) {
            return ['doi', $m[1]];
        }

        if (preg_match('~^https?://~i', $s)) {
            return ['url', $s];
        }

        return ['url', $s];
    }
}
