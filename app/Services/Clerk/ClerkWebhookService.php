<?php

namespace App\Services\Clerk;

use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Log;

class ClerkWebhookService
{
    public function handle(array $event): void
    {
        $type = data_get($event, 'type');
        $data = data_get($event, 'data', []);

        match ($type) {
            'user.created', 'user.updated' => $this->syncUser($data),
            'user.deleted' => $this->handleDeletedUser($data),
            default => null,
        };
    }

    protected function syncUser(array $data): void
    {
        $clerkUserId = data_get($data, 'id');
        $externalId = data_get($data, 'external_id');

        if (!$clerkUserId || !$externalId) {
            return;
        }

        $person = Person::where('uuid', $externalId)->first();

        if (!$person) {
            Log::warning('Clerk webhook user sync skipped: person not found.', [
                'clerk_user_id' => $clerkUserId,
                'external_id' => $externalId,
            ]);
            return;
        }

        if (!$person->clerk_user_id || $person->clerk_user_id === $clerkUserId) {
            $person->forceFill([
                'clerk_user_id' => $clerkUserId,
            ])->save();
        }
    }

    protected function handleDeletedUser(array $data): void
    {
        Log::warning('Clerk user deleted.', [
            'clerk_user_id' => data_get($data, 'id'),
            'external_id' => data_get($data, 'external_id'),
        ]);
    }
}