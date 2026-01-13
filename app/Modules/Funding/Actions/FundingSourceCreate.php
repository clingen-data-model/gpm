<?php

namespace App\Modules\Funding\Actions;

use App\Modules\Funding\Models\FundingSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FundingSourceCreate
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()?->hasPermissionTo('ep-applications-manage'), 403);

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'funding_type_id' => ['required', 'integer', 'exists:funding_types,id'],
            'caption'     => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:250'],
            'logo'        => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif', 'max:3072', 'dimensions:max_width=600,max_height=600'],
        ]);

        if (array_key_exists('caption', $validated)) {
            $validated['caption'] = mb_substr(trim(strip_tags($validated['caption'] ?? '')), 0, 500);
        }

        $source = new FundingSource();
        $source->fill($validated);
        $source->save();

        if ($request->hasFile('logo')) {
            $dir = config('app.funding_sources_logo_dir', 'funding_sources/logos');
            $storedPath = $request->file('logo')->store($dir, 'public');
            $source->logo_path = basename($storedPath);
            $source->save();
        }

        return $source->fresh();
    }
}
