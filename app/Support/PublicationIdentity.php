<?php

namespace App\Support;

class PublicationIdentity
{
    // Match aliases supplied by the API, including records saved under different sources.
    public static function count(iterable $publications): int
    {
        $parents = [];
        $aliases = [];
        $root = function ($id) use (&$parents, &$root) {
            return $parents[$id] === $id ? $id : ($parents[$id] = $root($parents[$id]));
        };
        foreach ($publications as $publication) {
            $id = $publication->id;
            $parents[$id] = $id;
            $ids = [];
            foreach (['pmcid', 'pmid', 'doi', 'url'] as $source) {
                $value = data_get($publication->meta, "$source.id");
                if ($publication->source === $source) $ids[] = [$source, $publication->identifier];
                if ($value) $ids[] = [$source, $value];
            }
            foreach ($ids as [$source, $value]) {
                $value = trim((string) $value);
                if ($source === 'doi') $value = preg_replace('#^(?:https?://(?:dx\.)?doi\.org/|doi:\s*)#i', '', $value);
                if ($source !== 'url') $value = strtolower($value);
                if ($value === '') continue;
                $key = $source.':'.$value;
                if (isset($aliases[$key])) $parents[$root($id)] = $root($aliases[$key]);
                $aliases[$key] = $id;
            }
        }
        return count(array_unique(array_map($root, array_keys($parents))));
    }
}
