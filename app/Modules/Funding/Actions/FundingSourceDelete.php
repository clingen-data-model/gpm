<?php

namespace App\Modules\Funding\Actions;

use App\Modules\Funding\Models\FundingSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FundingSourceDelete
{
    public function __invoke(Request $request, FundingSource $fundingSource)
    {
        abort_unless($request->user()?->hasPermissionTo('ep-applications-manage'), 403);
        
        if ($fundingSource->logo_path) {
            Storage::disk('public')->delete($fundingSource->logo_path);
        }

        $fundingSource->delete();

        return response()->json(['deleted' => true]);
    }
}
