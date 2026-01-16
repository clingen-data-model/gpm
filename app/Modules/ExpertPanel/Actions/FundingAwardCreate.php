<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\FundingAward;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\ActionRequest;

class FundingAwardCreate
{
    use AsObject, AsController;

    public function handle(ExpertPanel $expertPanel, array $data): FundingAward
    {
        $award = FundingAward::create(array_merge($data, [
            'expert_panel_id' => $expertPanel->id,
        ]));

        return $award->fresh(['fundingSource.fundingType']);
    }


    public function asController(ActionRequest $request, ExpertPanel $expertPanel)
    {
        $validated = $request->validated();
        return $this->handle($expertPanel, $validated);
    }

    public function authorize(ActionRequest $request): bool
    {
        return (bool) $request->user()?->hasPermissionTo('ep-applications-manage');
    }

    public function rules(ActionRequest $request): array
    {
        return [
            'funding_source_id' => ['required', 'integer', Rule::exists('funding_sources', 'id')],
            'award_number'      => ['nullable', 'string', 'max:30'],
            'start_date'        => ['nullable', 'date'],
            'end_date'          => ['nullable', 'date', 'after_or_equal:start_date'],

            'nih_reporter_url'  => ['nullable', 'string', 'max:255', 'url'],
            'nih_ic'            => ['nullable', 'string', 'max:255'],

            'contact_1_role'    => ['nullable', 'string', 'max:255'],
            'contact_1_name'    => ['nullable', 'string', 'max:255'],
            'contact_1_email'   => ['nullable', 'string', 'max:255', 'email'],
            'contact_1_phone'   => ['nullable', 'string', 'max:255'],

            'contact_2_role'    => ['nullable', 'string', 'max:255'],
            'contact_2_name'    => ['nullable', 'string', 'max:255'],
            'contact_2_email'   => ['nullable', 'string', 'max:255', 'email'],
            'contact_2_phone'   => ['nullable', 'string', 'max:255'],

            'notes'             => ['nullable', 'string'],
        ];

    }

    public function getValidationMessages(): array
    {
        return [];
    }
}
