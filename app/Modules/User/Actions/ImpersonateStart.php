<?php

namespace App\Modules\User\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\User\Models\ActiveImpersonation;
use App\Modules\User\Models\User;

class ImpersonateStart
{
    use AsAction;

    public function handle(User $impersonator, User $target): ActiveImpersonation
    {
        return ActiveImpersonation::updateOrCreate(
            ['impersonator_id' => $impersonator->id],
            ['target_user_id' => $target->id]
        );
    }

    public function authorize(ActionRequest $request): bool
    {
        return Auth::user()->canImpersonate();
    }

    public function asController(ActionRequest $request, User $user): JsonResponse
    {
        if (! $user->canBeImpersonated()) {
            return response()->json(['message' => 'This user cannot be impersonated.'], 403);
        }

        $this->handle(Auth::user(), $user);

        return response()->json(['message' => 'Impersonation started.']);
    }
}
