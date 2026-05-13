<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;
use Svix\Webhook;
use Svix\Exception\WebhookVerificationException;
use App\Services\Clerk\ClerkWebhookService;

class ClerkWebhookReceive
{
    use AsController;

    public function __construct(
        private ClerkWebhookService $clerkWebhookService
    ) {
    }

    public function asController(Request $request)
    {
        $payload = $request->getContent();
        $headers = collect($request->headers->all())
            ->map(fn ($value) => $value[0] ?? null)
            ->filter()
            ->all();

        try {
            $webhook = new Webhook(config('clerk.webhook_signing_secret'));
            $event = $webhook->verify($payload, $headers);
        } catch (WebhookVerificationException $e) {
            return response()->json(['message' => 'Invalid Clerk webhook signature.'], 400);
        }

        $this->clerkWebhookService->handle((array) $event);

        return response()->noContent();
    }
}