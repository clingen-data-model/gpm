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

        $clerkUserId = $request->attributes->get('clerk_user_id');

        if (!$clerkUserId) {
            return response()->json([
                'message' => 'Missing Clerk user ID.',
            ], 401);
        }

        $invite = Invite::with('person')->where('code', $data['code'])->firstOrFail();

        if ($invite->redeemed_at) {
            // Re-running the redeem is only a no-op for the account it was redeemed into;
            // for anyone else holding the code it must not confirm the person behind it.
            if ($invite->person && $invite->person->clerk_user_id === $clerkUserId) {
                return response()->json([
                    'message' => 'Invitation already redeemed.',
                    'person_uuid' => $invite->person->uuid,
                ]);
            }

            return response()->json([
                'message' => 'This invitation has already been redeemed.',
            ], 403);
        }

        if ($invite->expires_at && $invite->expires_at->isPast()) {
            return response()->json([
                'message' => 'This invitation has expired. Please request a new invitation.',
            ], 410);
        }

        $person = $this->clerkUserLinkService->linkInvite($invite, $clerkUserId);

        return response()->json([
            'message' => 'Invitation redeemed successfully.',
            'person_uuid' => $person->uuid,
            'clerk_user_id' => $person->clerk_user_id,
        ]);
    }
}