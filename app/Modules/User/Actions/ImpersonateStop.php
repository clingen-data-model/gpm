<?php

namespace App\Modules\User\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\User\Models\ActiveImpersonation;

class ImpersonateStop
{
    use AsAction;

    public function handle(int $impersonatorId): void
    {
        ActiveImpersonation::where('impersonator_id', $impersonatorId)->delete();
    }

    public function authorize(ActionRequest $request): bool
    {
        // Any authenticated user can stop their own impersonation session.
        return Auth::check();
    }

    public function asController(ActionRequest $request): JsonResponse
    {
        $this->handle(Auth::user()->id);

        return response()->json(['message' => 'Impersonation stopped.']);
    }
}
