<?php

namespace App\Support;

class PublicationTitle
{
    public static function plain(?string $title): ?string
    {
        if ($title === null) return null;
        // Decode one layer only; never interpret the result as executable HTML.
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', '', $title);
        $title = preg_replace('#<!--.*?-->|</?[a-z][a-z0-9]*\b[^>]*>#is', '', $title);
        // Known reversed formatting tags from upstream, preserving comparisons.
        $title = preg_replace('#>/?(?:i|b|em|strong|sup|sub)<#i', '', $title);
        return trim(preg_replace('/\s+/u', ' ', $title));
    }

    public static function metadata(?array $meta): ?array
    {
        if (isset($meta['title'])) $meta['title'] = self::plain($meta['title']);
        return $meta;
    }
}
