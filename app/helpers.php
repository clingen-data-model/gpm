<?php

if (!function_exists('renderQuery')) {
    function renderQuery($query)
    {
        $treated = preg_replace('/\?/', '"%s"', $query->toSql());

        return call_user_func_array('sprintf', array_merge([$treated], $query->getBindings()));
    }
}
