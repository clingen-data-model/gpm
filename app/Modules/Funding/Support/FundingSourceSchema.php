<?php

namespace App\Modules\Funding\Support;

use App\Modules\Funding\Models\FundingSource;

final class FundingSourceSchema
{
    public static function forMessage(FundingSource $source): array
    {
        $source->loadMissing('fundingType');

        return [
            'uuid'        => (string) $source->uuid,
            'name'        => $source->name,
            'funding_type'=> $source->fundingType?->name,
            'caption'     => $source->caption,
            'website_url' => $source->website_url,
            'logo_path'   => $source->logo_url_raw,
        ];
    }
}
