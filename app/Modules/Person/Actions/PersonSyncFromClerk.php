<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;
use App\Services\Clerk\ClerkUserLinkService;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use App\Services\UserIdentityNormalizer;

class PersonSyncFromClerk
{
    use AsController;

    public function __construct(
        private ClerkUserLinkService $clerkUserLinkService
    ) {
    }

    public function handle(Person $person): Person
    {
        return $this->clerkUserLinkService->syncPersonAndUserFromClerk($person);
    }

    public function asController(ActionRequest $request, Person $person)
    {
        return response()->json([
            'data' => $this->handle($person),
        ]);
    }

    public function authorize(ActionRequest $request): bool
    {
        $person = $request->route('person');
        return $request->user()->can('people-manage') || optional($request->user()->person)->id === optional($person)->id;
    }
}