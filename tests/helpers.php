<?php

if (!function_exists('stripSpaces')) {
    function stripSpaces (string $string): string
    {
        return preg_replace('/\s|&nbsp;/', '', $string);
    }
}

if (!function_exists('stripTagsAndSpaces')) {
    function stripTagsAndSpaces (string $string): string
    {
        return stripSpaces(strip_tags($string));
    }
}
