<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use App\Services\Clerk\ClerkUserLinkService;
use App\Modules\Person\Models\Invite;

class ClerkRedeemInvitation
{
    use AsController;

    public function __construct(
        private ClerkUserLinkService $clerkUserLinkService
    ) {
    }

    public function asController(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $invite = Invite::with('person')->where('code', $data['code'])->firstOrFail();

        if ($invite->redeemed_at) {
            return response()->json([
                'message' => 'Invitation already redeemed.',
                'person_uuid' => optional($invite->person)->uuid,
            ]);
        }

        $clerkUserId = $request->attributes->get('clerk_user_id');

        if (!$clerkUserId) {
            return response()->json([
                'message' => 'Missing Clerk user ID.',
            ], 401);
        }

        $person = $this->clerkUserLinkService->linkInvite($invite, $clerkUserId);

        return response()->json([
            'message' => 'Invitation redeemed successfully.',
            'person_uuid' => $person->uuid,
            'clerk_user_id' => $person->clerk_user_id,
        ]);
    }
}