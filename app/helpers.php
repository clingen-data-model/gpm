<?php

if (!function_exists('renderQuery')) {
    function renderQuery($query)
    {
        $treated = preg_replace('/\?/', '"%s"', $query->toSql());

        return call_user_func_array('sprintf', array_merge([$treated], $query->getBindings()));
    }
}

if (!function_exists('backtrace')) {
    function backtrace($limit = 0) {
        return array_map(function ($result) {
            return $result['file'].':'.$result['line'];
        }, debug_backtrace(null, $limit));
    }
}