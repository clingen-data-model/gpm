<?php

namespace App\Modules\Funding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class FundingSource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'funding_type_id',
        'caption',
        'website_url',
        'logo_path',
    ];

    protected $appends = ['logo_url', 'logo_url_raw'];

    protected function computePublicLogoUrl(bool $withVersion = true): ?string
    {
        if (!$this->logo_path) return null;

        $dir  = config('app.funding_sources_logo_dir', 'funding_sources/logos');
        $rel  = "{$dir}/{$this->logo_path}";
        $disk = Storage::disk('public');

        if (!$disk->exists($rel)) return null;

        $url = route('funding.logo', ['logo_path' => $this->logo_path]);

        if (!$withVersion) return $url;

        $ver = $disk->lastModified($rel);
        return "{$url}?v={$ver}";
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(get: fn () => $this->computePublicLogoUrl(true));
    }

    protected function logoUrlRaw(): Attribute
    {
        return Attribute::make(get: fn () => $this->computePublicLogoUrl(false));
    }

    public function fundingType() {
        return $this->belongsTo(FundingType::class);
    }

}
