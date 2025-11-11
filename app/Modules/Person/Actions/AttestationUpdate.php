<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Attestation;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Modules\Person\Events\AttestationCompleted;

class AttestationUpdate
{
    use AsController;

    public function handle(Person $person, array $data): Attestation
    {
        return DB::transaction(function () use ($person, $data) {
            $attestation = Attestation::query()->where('person_id', $person->id)->whereNull('revoked_at')->whereNull('deleted_at')->first();

            if(!$attestation) { // IF IT DOESNT EXIST, CREATE ONE
                $attestation = Attestation::create(['person_id' => $person->id]);
            }

            $attestation->fill([
                'experience_type'     => $data['experience_type'],
                'other_text'          => $data['other_text'] ?? null,
                'attestation_version' => Attestation::CURRENT_VERSION,
                'attested_by'         => optional(auth()->user())->id,
                'attested_at'         => now(),
            ])->save();
            Event::dispatch(new AttestationCompleted($person, $attestation));

            return $attestation->refresh();
        });
    }

    public function asController(ActionRequest $request, Person $person)
    {
        Log::info($request->only('experience_type', 'other_text'));
        $attestation = $this->handle($person, $request->only('experience_type', 'other_text'));
        return $attestation;
    }

    public function rules(): array
    {
        return [
            'experience_type'   => ['required', Rule::in([Attestation::TYPE_DIRECT, Attestation::TYPE_REVIEW, Attestation::TYPE_FIFTY, Attestation::TYPE_OTHER])],
            'other_text'        => 'nullable|required_if:experience_type,other|string|max:5000',
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        $person = $request->route('person');
        return $request->user()?->can('update', $person) ?? false;
    }
}
