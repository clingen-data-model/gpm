<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\FundingAward;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

class FundingAwardDelete
{
    use AsObject, AsController;

    public function handle(ExpertPanel $expertPanel, FundingAward $fundingAward): void
    {
        abort_unless((int) $fundingAward->expert_panel_id === (int) $expertPanel->id, 404);
        $fundingAward->delete();
    }

    public function asController(ActionRequest $request, ExpertPanel $expertPanel, FundingAward $fundingAward)
    {
        $this->handle($expertPanel, $fundingAward);
        return response()->noContent();
    }

    public function authorize(ActionRequest $request): bool
    {
        return (bool) $request->user()?->hasAnyRole(['super-user', 'super-admin']);
    }

    public function rules(ActionRequest $request): array
    {
        return [];
    }

    public function getValidationMessages(): array
    {
        return [];
    }
}
