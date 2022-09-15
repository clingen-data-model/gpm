<?php

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

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

if (!function_exists('valueAtIndex')) {
    function valueAtIndex(array $arr, $index, $default = null)
    {
        return isset($arr[$index]) ? $arr[$index]  : $default;
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

if (!function_exists('getClassesInPaths')) {
    function getClassesInPaths($paths, $namespace = null) {
        $paths = array_unique(Arr::wrap($paths));
        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        $classes = [];
        foreach ((new Finder())->in($paths)->files() as $command) {
            $classes[] = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($command->getPathname(), realpath(app_path()).DIRECTORY_SEPARATOR)
            );
        }

        return $classes;
    }
}
