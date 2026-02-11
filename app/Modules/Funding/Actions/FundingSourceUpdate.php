<?php

namespace App\Modules\Funding\Actions;

use App\Modules\Funding\Models\FundingSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FundingSourceUpdate
{
    public function __invoke(Request $request, FundingSource $fundingSource)
    {
        abort_unless($request->user()?->can('update', $fundingSource), 403);

        $validated = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'funding_type_id' => ['sometimes', 'required', 'integer', 'exists:funding_types,id'],
            'caption'     => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'string', 'max:250'],
            'logo'        => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif', 'max:3072', 'dimensions:max_width=600,max_height=600'],
            'remove_logo' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('caption', $validated)) {
            $validated['caption'] = mb_substr(trim(strip_tags($validated['caption'] ?? '')), 0, 500);
        }

        $fundingSource->fill($validated);

        $dir  = config('app.funding_sources_logo_dir', 'funding_sources/logos');
        $disk = Storage::disk('public');

        $deleteExistingLogo = function () use ($fundingSource, $dir, $disk) {
            if (!$fundingSource->logo_path) return;

            $old = $fundingSource->logo_path;
            $oldRel = "{$dir}/{$old}";
            $disk->delete($oldRel);

            $oldBase = pathinfo($old, PATHINFO_FILENAME);
            $exts = config('app.funding_logo_exts', ['png', 'jpg', 'jpeg', 'gif']);
            foreach ($exts as $ext) {
                $candidate = "{$dir}/{$oldBase}.{$ext}";
                if ($candidate !== $oldRel) {
                    $disk->delete($candidate);
                }
            }
        };

        // If a new logo is uploaded, it overrides remove_logo
        if ($request->hasFile('logo')) {
            $deleteExistingLogo();

            $storedPath = $request->file('logo')->store($dir, 'public');
            $fundingSource->logo_path = basename($storedPath);
        } else {
            // Only remove when no new logo is uploaded
            if ($request->boolean('remove_logo')) {
                $deleteExistingLogo();
                $fundingSource->logo_path = null;
            }
        }

        $fundingSource->save();

        return $fundingSource->fresh();
    }
}
