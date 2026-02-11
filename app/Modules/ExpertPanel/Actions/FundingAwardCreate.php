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
        if (array_key_exists('notes', $data)) {
            $data['notes'] = trim(strip_tags($data['notes'] ?? ''));
        }        
        $piIds = collect($data['contact_pi_person_ids'] ?? []);
        $primaryId = isset($data['primary_contact_pi_id']) ? (int) $data['primary_contact_pi_id'] : null;

        $repKeys = [
            'contact_1_role','contact_1_name','contact_1_email','contact_1_phone',
            'contact_2_role','contact_2_name','contact_2_email','contact_2_phone',
        ];
        $hasAnyRepField = collect($repKeys)->contains(fn ($k) => array_key_exists($k, $data));
        if ($hasAnyRepField) {
            $data['rep_contacts'] = [
                [
                    'role'  => $data['contact_1_role']  ?? null,
                    'name'  => $data['contact_1_name']  ?? null,
                    'email' => $data['contact_1_email'] ?? null,
                    'phone' => $data['contact_1_phone'] ?? null,
                ],
                [
                    'role'  => $data['contact_2_role']  ?? null,
                    'name'  => $data['contact_2_name']  ?? null,
                    'email' => $data['contact_2_email'] ?? null,
                    'phone' => $data['contact_2_phone'] ?? null,
                ],
            ];
        }
        unset(
            $data['contact_1_role'], $data['contact_1_name'], $data['contact_1_email'], $data['contact_1_phone'],
            $data['contact_2_role'], $data['contact_2_name'], $data['contact_2_email'], $data['contact_2_phone'],
        );
        unset($data['contact_pi_person_ids'], $data['primary_contact_pi_id']);
        
        $fundingAward = FundingAward::create(array_merge($data, [
            'expert_panel_id' => $expertPanel->id,
        ]));

        if ($primaryId && !$piIds->contains($primaryId)) {
            $piIds = $piIds->push($primaryId)->unique()->values();
        }
        $fundingAward->contactPis()->sync(
            $piIds->mapWithKeys(fn ($id) => [$id => ['is_primary' => $primaryId === $id]])->all()
        );

        return $fundingAward->fresh(['fundingSource.fundingType', 'contactPis']);
    }


    public function asController(ActionRequest $request, ExpertPanel $expertPanel)
    {
        $validated = $request->validated();
        return $this->handle($expertPanel, $validated);
    }

    public function authorize(ActionRequest $request): bool
    {
        return (bool) $request->user()?->hasAnyRole(['super-user', 'super-admin']);
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

            'contact_pi_person_ids'   => ['nullable', 'array'],
            'contact_pi_person_ids.*' => ['integer', 'exists:people,id'],
            'primary_contact_pi_id'   => ['nullable', 'integer', 'exists:people,id'],
        ];

    }

    public function getValidationMessages(): array
    {
        return [];
    }
}
