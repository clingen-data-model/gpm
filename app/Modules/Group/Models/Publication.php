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


}