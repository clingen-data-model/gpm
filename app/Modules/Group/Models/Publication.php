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
        'source',
        'identifier',
        'meta',
        'pub_type',
        'published_at',
        'status',
        'error',
    ];

    protected $casts = [
        'meta' => 'array',
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

    protected function displayTitle(): Attribute
    {
        return Attribute::get(function () {
            $raw = $this->meta['title'] ?? null;

            if (!$raw) { $raw = $this->meta['titleText'] ?? null; }
            $raw = $raw ?: '(untitled)';
            $clean = strip_tags((string) $raw);
            return Str::limit($clean, 120);
        });
    }

    protected function displayLink(): Attribute
    {
        return Attribute::get(function () {
            return $this->link
                ?? ($this->meta['url'] ?? null)
                ?? (isset($this->meta['pmid'])  ? "https://pubmed.ncbi.nlm.nih.gov/{$this->meta['pmid']}/" : null)
                ?? (isset($this->meta['pmcid']) ? "https://www.ncbi.nlm.nih.gov/pmc/articles/".strtoupper($this->meta['pmcid'])."/" : null)
                ?? (isset($this->meta['doi'])   ? "https://doi.org/{$this->meta['doi']}" : null);
        });
    }

}
