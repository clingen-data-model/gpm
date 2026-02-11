<?php

namespace App\Modules\Funding\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Funding\Models\FundingSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Modules\Funding\Policies\FundingSourcePolicy;

class FundingSourceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', FundingSource::class);
        return FundingSource::with('fundingType')->orderBy('name')->get();
    }

    public function show(FundingSource $fundingSource)
    {
        $this->authorize('view', $fundingSource);
        return $fundingSource;
    }

    public function logo(Request $request, string $logo_path)
    {
        $dir  = config('app.funding_sources_logo_dir', 'funding_sources/logos');
        $rel  = "{$dir}/{$logo_path}";
        $disk = Storage::disk('public');

        abort_unless($disk->exists($rel), 404);

        $response = response()->file($disk->path($rel));
        return $response;
    }

}
