<?php

namespace App\Modules\User\Actions;

use Svix\Webhook;
use Svix\Exception\WebhookVerificationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\User\Models\User;

class HandleClerkWebhook
{
    use AsAction;

    public function handle(string $eventType, array $data): void
    {
        match ($eventType) {
            'user.updated' => $this->syncUser($data),
            'user.deleted' => $this->deactivateUser($data),
            default => null,
        };
    }

    public function asController(Request $request): JsonResponse
    {
        $secret = config('clerk.webhook_secret');

        if (! $secret) {
            return response()->json(['error' => 'Webhook secret not configured.'], 500);
        }

        try {
            $wh = new Webhook($secret);
            $payload = $wh->verify($request->getContent(), $request->headers->all());
        } catch (WebhookVerificationException $e) {
            return response()->json(['error' => 'Invalid signature.'], 401);
        }

        $this->handle($payload['type'] ?? '', $payload['data'] ?? []);

        return response()->json(['status' => 'ok']);
    }

    private function syncUser(array $data): void
    {
        $clerkUserId = $data['id'] ?? null;
        if (! $clerkUserId) {
            return;
        }

        $user = User::where('clerk_user_id', $clerkUserId)->first();
        if (! $user) {
            // Option A provisioning: ignore Clerk users not yet provisioned in GPM.
            return;
        }

        $updates = [];

        $primaryEmail = $this->primaryEmail($data);
        if ($primaryEmail && $primaryEmail !== $user->email) {
            $updates['email'] = $primaryEmail;
        }

        $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        if ($fullName && $fullName !== $user->name) {
            $updates['name'] = $fullName;
        }

        if ($updates) {
            $user->update($updates);
        }
    }

    private function deactivateUser(array $data): void
    {
        $clerkUserId = $data['id'] ?? null;
        if (! $clerkUserId) {
            return;
        }

        User::where('clerk_user_id', $clerkUserId)->update(['active' => false]);
    }

    private function primaryEmail(array $data): ?string
    {
        $primaryId = $data['primary_email_address_id'] ?? null;
        foreach ($data['email_addresses'] ?? [] as $addr) {
            if ($addr['id'] === $primaryId) {
                return $addr['email_address'] ?? null;
            }
        }
        return null;
    }
}
