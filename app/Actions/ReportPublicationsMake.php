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
                'publishedPublication.group',
                'preprint.group',
            ])
            ->get();

        $rows = $pubs->map(function ($pub) {
            $base = $pub->toExchangePayload();

            $authors = $base['authors'] ?? [];
            if (is_array($authors)) {
                $authors = implode('; ', array_filter($authors));
            }

            $identifiers = [];
            if ($base['meta']['pmid']['id'] ?? null) { $identifiers[] = strtoupper("PMID: ") . $base['meta']['pmid']['id']; }
            if ($base['meta']['doi']['id'] ?? null) { $identifiers[] = strtoupper("DOI: ") . $base['meta']['doi']['id']; }
            if ($base['meta']['pmcid']['id'] ?? null) { $identifiers[] = strtoupper("PMCID: ") . $base['meta']['pmcid']['id']; }

            $group         = $pub->group;
            $groupType     = $group?->type?->display_name;
            $groupName     = $group?->name ?? $group?->displayName;
            $groupUuid     = $group?->uuid;
            $affiliationID = $group?->affiliation_id;

            $row = [
                // From Publication::toExchangePayload
                'publication_id'    => $base['publication_id'] ?? null,
                'type'        => $base['type'] ?? null,
                'title'       => $base['title'] ?? null,
                'authors'     => $authors,
                'journal'     => $base['journal'] ?? null,
                'identifiers' => $identifiers,
                'published_at'   => $base['published_at'] ?? null,
                'url'         => $base['url'] ?? null,
                'added_to_gpm'   => $base['created_at'] ?? null,

                // From Group
                'group_uuid'       => $groupUuid,
                'group_name'       => $groupName,
                'group_type'       => $groupType,
                'affiliation_id'   => $affiliationID,
            ];

            $row = array_merge($row, $pub->linkedPublicationReportFields());

            foreach ($row as $k => $v) {
                if (is_string($v)) {
                    $row[$k] = preg_replace('/\r?\n/', '; ', $v);
                }
            }

            return $row;
        });

        return $rows->toArray();
    }

    public function streamRows(callable $push): void
    {
        foreach ($this->handle() as $row) {
            $push($row);
        }
    }

    public function asCommand(Command $command)
    {
        dd($this->handle());
    }
}
