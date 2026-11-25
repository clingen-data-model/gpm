<?php

namespace App\Modules\Group\Actions;

use Illuminate\Support\Str;
use League\HTMLToMarkdown\HtmlConverter;

class InactivationText
{
    public static function clean(string $markdown): string
    {
        // Use the existing Markdown packages; never persist executable HTML/unsafe links.
        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        return (new HtmlConverter(['strip_tags' => true]))->convert($html);
    }

    public static function hasText(string $markdown): bool
    {
        return trim(html_entity_decode(strip_tags(Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false])))) !== '';
    }
}
