<?php

namespace App\Actions;

use Illuminate\Console\Command;
use App\Modules\Group\Models\Publication;

class ReportPublicationsMake extends ReportMakeAbstract
{
    public $commandSignature = 'report:publications';

    public function handle()
    {
        $pubs = Publication::with([
                'group',
                'group.type',
                'group.expertPanel',
            ])
            ->get();

        $rows = $pubs->map(function ($pub) {
            $base = $pub->toExchangePayload();

            $authors = $base['authors'] ?? [];
            if (is_array($authors)) {
                $authors = implode('; ', array_filter($authors));
            }

            $identifiers = $base['identifiers'] ?? null;
            if (is_array($identifiers)) {
                $parts = [];
                foreach ($identifiers as $key => $value) {
                    if ($value === null || $value === '') continue;
                    $parts[] = strtoupper($key) . ': ' . $value;
                }
                $identifiers = implode(', ', $parts);
            } elseif (!is_string($identifiers)) {
                $identifiers = '';
            }

            $group         = $pub->group;
            $groupType     = $group?->type?->display_name;
            $groupName     = $group?->name ?? $group?->displayName;
            $groupUuid     = $group?->uuid;
            $affiliationID = $group?->expertPanel?->affiliation_id;

            $row = [
                // From Publication
                'uuid'        => $base['uuid'] ?? null,
                'type'        => $base['type'] ?? null,
                'title'       => $base['title'] ?? null,
                'authors'     => $authors,
                'journal'     => $base['journal'] ?? null,
                'identifiers' => $identifiers,
                'published'   => $base['published'] ?? null,
                'url'         => $base['url'] ?? null,

                // From Group
                'group_uuid'       => $groupUuid,
                'group_name'       => $groupName,
                'group_type'       => $groupType,
                'affiliation_id'   => $affiliationID,
            ];

            foreach ($row as $k => $v) {
                if (is_string($v)) {
                    $row[$k] = preg_replace('/\r?\n/', '; ', $v);
                }
            }

            return $row;
        });

        return $rows->toArray();
    }

    public function asCommand(Command $command)
    {
        dd($this->handle());
    }
}
