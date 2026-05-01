<?php

namespace App\Modules\ExpertPanel\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\FundingAward;

class FundingAwardAgreementUpload
{
    use AsAction;

    public function asController(Request $request, ExpertPanel $expertPanel, FundingAward $fundingAward)
    {
        abort_unless((int) $fundingAward->expert_panel_id === (int) $expertPanel->id, 404);

        $request->validate([
            'partnership_agreement' => ['required', 'mimes:pdf,doc,docx', 'file', 'max:3072']
        ]);
        $file = $request->file('partnership_agreement');
        $extension = $file->getClientOriginalExtension();

        $filename = 'gpm-funding-award-agreement-' . $fundingAward->id . '-' . now()->format('YmdHi') . '.' . $extension;
        $oldFile = $fundingAward->partnership_agreement_file;
        $file->storeAs(config('app.funding_award_agreements_dir'), $filename, 'public');
        $fundingAward->update(['partnership_agreement_file' => $filename]);

        if ($oldFile) {
            Storage::disk('public')->delete(config('app.funding_award_agreements_dir') . '/' . $oldFile);
        }
        return $fundingAward->fresh();
    }

    public function authorize(ActionRequest $request): bool
    {
        return (bool) $request->user()?->hasAnyRole(['super-user', 'super-admin']) || $request->user()?->hasAnyPermission(['funding-sources-manage']);
    }
}
