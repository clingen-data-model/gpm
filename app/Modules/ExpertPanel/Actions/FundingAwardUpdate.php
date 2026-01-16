<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\FundingAward;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;

class FundingAwardUpdate
{
    use AsObject, AsController;

    public function handle(ExpertPanel $expertPanel, FundingAward $fundingAward, array $data): FundingAward
    {
        abort_unless((int) $fundingAward->expert_panel_id === (int) $expertPanel->id, 404);

        if (array_key_exists('notes', $data)) {
            $data['notes'] = trim(strip_tags($data['notes'] ?? ''));
        }
        $fundingAward->fill($data);
        $fundingAward->save();

        return $fundingAward->fresh(['fundingSource.fundingType']);
    }

    public function asController(ActionRequest $request, ExpertPanel $expertPanel, FundingAward $fundingAward)
    {
        $validated = $request->validated();
        return $this->handle($expertPanel, $fundingAward, $validated);
    }

    public function authorize(ActionRequest $request): bool
    {
        return (bool) $request->user()?->hasPermissionTo('ep-applications-manage');
    }

    public function rules(ActionRequest $request): array
    {
        return [
            'funding_source_id' => ['sometimes', 'required', 'integer', Rule::exists('funding_sources', 'id')],
            'award_number'      => ['nullable', 'string', 'max:100'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function getValidationMessages(): array
    {
        return [];
    }
}
