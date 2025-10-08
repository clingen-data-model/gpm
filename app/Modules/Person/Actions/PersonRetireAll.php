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

class PersonRetireAll
{
    use AsController, AsObject;

    public function __construct(
        private MemberRetire $memberRetire,
        private ?PersonUnlinkUser $unlinkUser = null 
    ) {}

    public function handle(Person $person, bool $unlinkUser = false, ?string $reason = null): array
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

        $didUnlink = false;
        if ($unlinkUser && $person->user) {
            if ($this->unlinkUser) {
                $this->unlinkUser->handle($person);
            } else {
                app(PersonUnlinkUser::class)->handle($person);
            }
            $didUnlink = true;
        }

        return [
            'person_id' => $person->id,
            'memberships_retired' => $retired,
            'unlinked_user' => $didUnlink,
        ];
    }

    public function asController(ActionRequest $request, Person $person)
    {
        $data = $request->validated();
        $unlinkUser = $request->boolean('unlink_user', true);
        $reason = $data['reason'] ?? null;
        return response()->json(
            $this->handle($person, $unlinkUser, $reason)
        );
    }

    public function rules(): array
    {
        return [
            'unlink_user' => ['boolean'],
            'reason'      => ['nullable','string','max:5000'],
        ];
    }

    public function authorize(ActionRequest $request): bool
    {
        return $request->user()->can('people-manage');
    }
}
