<?php

namespace App\Modules\Group\Actions;

class InactivationUploadLimit
{
    public static function kilobytes(): int
    {
        $upload = ini_parse_quantity(ini_get('upload_max_filesize'));
        $post = ini_parse_quantity(ini_get('post_max_size'));
        // Reserve multipart/form overhead below PHP's whole-request limit.
        return max(1, (int) floor(min(20 * 1024 * 1024, $upload > 0 ? $upload : PHP_INT_MAX,
            $post > 0 ? max(1024, $post - 64 * 1024) : PHP_INT_MAX) / 1024));
    }
}
