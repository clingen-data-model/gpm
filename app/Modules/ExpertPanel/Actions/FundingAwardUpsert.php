<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Events\FundingAwardCreated;
use App\Modules\ExpertPanel\Events\FundingAwardUpdated;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\FundingAward;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;
use Ramsey\Uuid\Uuid;

class FundingAwardUpsert
{
    use AsObject, AsController;

    public function handle(ExpertPanel $expertPanel, array $data, ?FundingAward $fundingAward = null): FundingAward 
    {
        $isCreate = $fundingAward === null;
        if ($fundingAward) {
            abort_unless((int) $fundingAward->expert_panel_id === (int) $expertPanel->id, 404);
        }
        $originalData = $data;
        $data = $this->normalizeData($data);

        if ($isCreate) {
            $data['uuid'] = Uuid::uuid4()->toString();
            $data['expert_panel_id'] = $expertPanel->id;
            $fundingAward = FundingAward::create($data);
        } else {
            $fundingAward->fill($data);
            $fundingAward->save();
        }

        $this->syncContactPis($fundingAward, $originalData, $isCreate);
        Event::dispatch($isCreate ? new FundingAwardCreated($expertPanel, $fundingAward) : new FundingAwardUpdated($expertPanel, $fundingAward));
        return $fundingAward->fresh(['fundingSource.fundingType', 'contactPis']);
    }

    public function asController(ActionRequest $request, ExpertPanel $expertPanel, ?FundingAward $fundingAward = null) 
    {
        return $this->handle($expertPanel, $request->validated(), $fundingAward);
    }

    public function authorize(ActionRequest $request): bool
    {
        return (bool) $request->user()?->hasAnyRole(['super-user', 'super-admin']);
    }

    public function rules(ActionRequest $request): array
    {
        $isUpdate = (bool) $request->route('fundingAward');

        return [
            'funding_source_id' => $isUpdate ? ['sometimes', 'required', 'integer', Rule::exists('funding_sources', 'id')] : ['required', 'integer', Rule::exists('funding_sources', 'id')],

            'award_number'             => ['nullable', 'string', 'max:30'],
            'start_date'               => ['nullable', 'date'],
            'end_date'                 => ['nullable', 'date', 'after_or_equal:start_date'],
            'award_url'                => ['nullable', 'string', 'max:255', 'url'],
            'funding_source_division'  => ['nullable', 'string', 'max:255'],

            'rep_contacts'             => ['nullable', 'array'],
            'rep_contacts.*.role'      => ['nullable', 'string', 'max:100'],
            'rep_contacts.*.name'      => ['nullable', 'string', 'max:250'],
            'rep_contacts.*.email'     => ['nullable', 'string', 'max:255', 'email'],
            'rep_contacts.*.phone'     => ['nullable', 'string', 'max:25'],
            'notes'                    => ['nullable', 'string'],

            'contact_pi_person_ids'    => ['nullable', 'array'],
            'contact_pi_person_ids.*'  => ['integer', 'exists:people,id'],
            'primary_contact_pi_id'    => ['nullable', 'integer', 'exists:people,id'],
        ];
    }

    public function getValidationMessages(): array
    {
        return [];
    }

    private function normalizeData(array $data): array
    {
        if (array_key_exists('notes', $data)) {
            $data['notes'] = trim(strip_tags($data['notes'] ?? ''));
        }

        if (array_key_exists('rep_contacts', $data)) {
            $data['rep_contacts'] = $this->normalizeRepContacts($data['rep_contacts'] ?? []);
        }
        unset($data['contact_pi_person_ids'], $data['primary_contact_pi_id']);
        return $data;
    }

    private function normalizeRepContacts(array $contacts): array
    {
        return collect($contacts)->map(function ($contact) {
            return [
                'role'  => $this->normalizeString($contact['role'] ?? null),
                'name'  => $this->normalizeString($contact['name'] ?? null),
                'email' => $this->normalizeString($contact['email'] ?? null),
                'phone' => $this->normalizeString($contact['phone'] ?? null),
            ];
        })->filter(function ($contact) {
            return filled($contact['role']) || filled($contact['name']) || filled($contact['email']) || filled($contact['phone']);
        })->values()->all();
    }

    private function syncContactPis(FundingAward $fundingAward, array $data, bool $isCreate): void
    {
        $hasPiIds = array_key_exists('contact_pi_person_ids', $data);
        $hasPrimary = array_key_exists('primary_contact_pi_id', $data);

        if (! $isCreate && ! $hasPiIds && ! $hasPrimary) {
            return;
        }

        $fundingAward->loadMissing('contactPis');
        $piIds = $hasPiIds ? collect($data['contact_pi_person_ids'] ?? []) : $fundingAward->contactPis->pluck('id');
        $piIds = $piIds->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $primaryId = filled($data['primary_contact_pi_id'] ?? null) ? (int) $data['primary_contact_pi_id'] : null;
        if ($primaryId && ! $piIds->contains($primaryId)) {
            $piIds = $piIds->push($primaryId)->unique()->values();
        }

        $fundingAward->contactPis()->sync($piIds->mapWithKeys(fn ($id) => [$id => ['is_primary' => $primaryId === $id]])->all());
    }

    private function normalizeString($value): ?string
    {
        if ($value === null) return null;
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}