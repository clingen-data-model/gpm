<?php

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Publication extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'group_id',
        'added_by_id',
        'updated_by_id',
        'link',
        'source',
        'identifier',
        'meta',
        'pub_type',
        'published_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'sent_to_dx_at' => 'datetime',
        'published_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function ($m) {
            $m->uuid ??= (string) Str::uuid();
        });
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function toExchangePayload(): array
    {
        return [
            'publication_id'    => $this->uuid,
            'type'              => $this->pub_type,
            'source_type'       => $this->source,
            'source_id'         => $this->identifier,
            'title'             => $this->meta['title'] ?? null,
            'authors'           => $this->meta['authors'] ?? [],
            'journal'           => $this->meta['journal'] ?? null,            
            'published_at'      => $this->published_at ?? $this->meta['published_at'] ?? null,
            'url'               => $this->link ?? null,
            'meta'              => $this->meta ?? []
        ];
    }


    protected function displayJournal(): Attribute
    {
        return Attribute::get(function () {
            $m = (array) ($this->meta ?? []);
            return $m['journal']
                ?? $m['journalTitle']
                ?? (is_array($m['container-title'] ?? null) ? ($m['container-title'][0] ?? null) : ($m['container-title'] ?? null))
                ?? null;
        });
    }

    protected function displayType(): Attribute
    {
        return Attribute::get(fn () => $this->pub_type ?: (($this->meta['pubType'] ?? null) ?: ($this->meta['type'] ?? null)));
    }

    protected function displayDate(): Attribute
    {
        return Attribute::get(function () {
            if ($this->published_at) return $this->published_at->toDateString();
            $m = (array) ($this->meta ?? []);
            if (!empty($m['firstPublicationDate'])) return $m['firstPublicationDate'];
            if (!empty($m['pubdate'])) return $m['pubdate'];
            $parts = $m['issued']['date-parts'][0] ?? null;
            if ($parts) {
                $y = $parts[0] ?? null; $mo = $parts[1] ?? 1; $d = $parts[2] ?? 1;
                return $y ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : null;
            }
            return null;
        });
    }

    public function identifiers(): array
    {
        $m = (array) ($this->meta ?? []);
        return array_filter([
            'pmid'  => $m['pmid']  ?? $m['pmId'] ?? null,
            'pmcid' => isset($m['pmcid']) ? Str::upper((string) $m['pmcid']) : null,
            'doi'   => $m['doi']   ?? $m['DOI'] ?? null,
        ]);
    }

    public function authorsList(): array
    {
        $m = (array) ($this->meta ?? []);
        if (isset($m['authorList']['author']) && is_array($m['authorList']['author'])) {
            return array_values(array_filter(array_map(
                fn($a) => trim(implode(' ', array_filter([$a['firstName'] ?? null, $a['lastName'] ?? null]))) ?: ($a['initials'] ?? null),
                $m['authorList']['author']
            )));
        }
        if (isset($m['authors']) && is_array($m['authors'])) {
            return array_values(array_filter(array_map(
                fn($a) => $a['name'] ?? trim(implode(' ', array_filter([$a['first'] ?? null, $a['last'] ?? null]))),
                $m['authors']
            )));
        }
        if (isset($m['author']) && is_array($m['author'])) {
            return array_values(array_filter(array_map(
                fn($a) => trim(implode(' ', array_filter([$a['given'] ?? null, $a['family'] ?? null]))),
                $m['author']
            )));
        }
        return [];
    }

    public function toExchangePayload(): array
    {
        return [
            'uuid'        => $this->uuid,
            'type'        => $this->display_type,
            'title'       => $this->display_title,
            'authors'     => $this->authorsList(),
            'journal'     => $this->display_journal,
            'identifiers' => $this->identifiers(),
            'published'   => $this->display_date,
            'url'         => $this->display_link,
        ];
    }


}
