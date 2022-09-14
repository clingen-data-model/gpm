<?php

use Carbon\Carbon;

if (!function_exists('renderQuery')) {
    function renderQuery($query)
    {
        $treated = preg_replace('/\?/', '"%s"', $query->toSql());

        return call_user_func_array('sprintf', array_merge([$treated], $query->getBindings()));
    }
}

if (!function_exists('backtrace')) {
    function backtrace($limit = 0)
    {
        return array_map(function ($result) {
            return $result['file'].':'.$result['line'];
        }, debug_backtrace(0, $limit));
    }
}

if (!function_exists('carbonToString')) {
    function carbonToString(?Carbon $carbon, $format = 'Y-m-d H:i:s')
    {
        return $carbon ? $carbon->format($format) : null;
    }
}

if (!function_exists('test_path')) {
    function test_path(string $path) {
        return base_path('tests/'.$path);
    }
}

if (!function_exists('implementsInteface')) {
    function implementsInterface($object, $interface) {
        return in_array($interface, class_implements($object));
    }
}
