<?php

namespace App\Services\Clerk;

use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Invite;

class ClerkInvitationService
{
    public function __construct(
        private ClerkClientFactory $clientFactory
    ) {
    }

    public function createForInvite(Invite $invite, Group $group): array
    {
        $redirectUrl = config('clerk.invitation_redirect_url');
        $separator = str_contains($redirectUrl, '?') ? '&' : '?';
        $redirectUrl .= $separator . 'code=' . urlencode($invite->code);

        logger()->info('Sending Clerk invitation', [
            'email' => $invite->email,
            'redirect_url' => $redirectUrl,
            'group_uuid' => $group->uuid,
            'person_uuid' => $invite->person->uuid,
        ]);

        $response = $this->clientFactory->make()->post('/invitations', [
            'email_address' => $invite->email,
            'redirect_url' => $redirectUrl,
            'public_metadata' => [
                'person_uuid' => $invite->person->uuid,
                'invite_code' => $invite->code,
            ],
            'notify' => true,
            'expires_in_days' => 30,
        ]);

        logger()->info('Clerk invitation response', $response->json());

        $response->throw();
        
        logger()->info('Clerk invitation created successfully');

        return $response->json();
    }
}