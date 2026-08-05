<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;
use App\Services\Clerk\ClerkUserLinkService;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class PersonUpdateClerkAccount
{
    use AsController;

    public function __construct(
        private ClerkUserLinkService $clerkUserLinkService
    ) {
    }

    public function asController(ActionRequest $request, Person $person)
    {
        $data = $request->validated();

        $updatedPerson = $this->clerkUserLinkService->relinkPersonToClerkAccount(
            $person,
            $data['clerk_user_id']
        );

        return response()->json([
            'data' => $updatedPerson,
        ]);
    }

    public function rules(): array
    {
        return [
            'clerk_user_id' => ['required', 'string'],
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->hasAnyRole(['super-admin', 'super-user']);
    }
}