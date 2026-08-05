<?php

namespace App\Modules\Person\Actions;

use Carbon\Carbon;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\MemberRetire;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Modules\User\Actions\UserDelete;
use App\Services\Clerk\ClerkUserLinkService;

class PersonRetireAll
{
    use AsController, AsObject;

    public function __construct(
        private MemberRetire $memberRetire,
        private UserDelete $userDelete,
        private ClerkUserLinkService $clerkUserLinkService
    ) {}

    public function handle(Person $person, bool $disableLogin = false, ?string $reason = null): array
    {
        $actor = Auth::user();
        $endAt = Carbon::now();
        $retired = 0;

        foreach ($person->memberships()->isActive()->get() as $gm) {
            try {
                $this->memberRetire->handle($gm, $endAt, $reason, $actor);
                $retired++;
            } catch (\Throwable $e) {
                Log::warning('PersonRetireAll: membership retire failed', [
                    'person_id' => $person->id,
                    'group_member_id' => $gm->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $loginDisabled  = false;
        if ($disableLogin) {
            $user = $person->user()->first();
            $hadClerkLink = (bool) $person->clerk_user_id;

            if ($user || $hadClerkLink) {
                $this->clerkUserLinkService->unlinkGpmApplicationFromPerson($person);
                if ($user) {
                    $person->forceFill(['user_id' => null])->save();
                    $this->userDelete->handle($user);
                }
                $loginDisabled = true;
            }
        }

        return [
            'person_id' => $person->id,
            'memberships_retired' => $retired,
            'disable_login' => $loginDisabled,
        ];
    }

    public function asController(ActionRequest $request, Person $person)
    {
        $data = $request->validated();
        $disableLogin = $request->boolean('disable_login', false);
        $reason = $data['reason'] ?? null;
        return response()->json(
            $this->handle($person, $disableLogin, $reason)
        );
    }

    public function rules(): array
    {
        return [
            'disable_login' => ['boolean'],
            'reason'        => ['nullable','string','max:5000'],
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('people-manage');
    }
}
