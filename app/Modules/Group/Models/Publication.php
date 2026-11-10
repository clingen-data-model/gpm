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

    public function getMetaAttribute($value)
    {
        return \App\Support\PublicationTitle::metadata($this->fromJson($value));
    }

    public function publishedPublication()
    {
        return $this->belongsTo(self::class, 'published_publication_id');
    }

    public function preprint()
    {
        return $this->hasOne(self::class, 'published_publication_id');
    }

    public function linkedPublicationReportFields(): array
    {
        $linked = $this->publishedPublication ?? $this->preprint;
        return [
            'publication_relationship' => $linked ? ($this->publishedPublication ? 'Published version' : 'Linked preprint') : null,
            'linked_publication_title' => $linked?->meta['title'] ?? null,
            'linked_publication_pmcid' => $linked?->meta['pmcid']['id'] ?? ($linked?->source === 'pmcid' ? $linked->identifier : null),
            'linked_publication_gpm_url' => $linked?->group ? url('/groups/'.$linked->group->uuid.'?tab=publications&publication='.$linked->id) : null,
        ];
    }

    public function toExchangePayload(): array
    {
        return [
            'publication_id'    => $this->uuid,
            'type'              => $this->pub_type,
            'source_type'       => $this->source,
            'source_id'         => $this->identifier,
            'title'             => $this->meta['title'] ?? null,
            'title_format'      => 'plain',
            'authors'           => $this->meta['authors'] ?? [],
            'journal'           => $this->meta['journal'] ?? null,            
            'published_at'      => $this->published_at ?? $this->meta['published_at'] ?? null,
            'url'               => $this->link ?? null,
            'meta'              => $this->meta ?? [],
            'created_at'        => $this->created_at,
        ];
    }


}
