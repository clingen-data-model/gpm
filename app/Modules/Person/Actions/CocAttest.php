<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;
use App\Services\CocService;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class CocAttest
{
    use AsController;

    public function __construct(
        protected CocService $cocService
    ) {}

    public function handle(Person $person): array
    {
        $attestation = $this->cocService->attest($person);
        $person->loadMissing('latestCocAttestation');

        return [
            'ok' => true,
            'saved' => [
                'uuid'         => $attestation->uuid,
                'version'      => $attestation->version,
                'completed_at' => optional($attestation->completed_at)->toIso8601String(),
                'expires_at'   => optional($attestation->expires_at)->toIso8601String(),
            ],
            'status' => $this->cocService->statusFor($person),
        ];
    }

    public function asController(ActionRequest $request): array
    {
        $person = Auth::user()->person;
        return $this->handle($person);
    }

    public function rules(): array
    {
        return [
            'agreed' => ['required', 'boolean', 'in:1,true'],
        ];
    }
}
