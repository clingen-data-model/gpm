<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsController;

class ClientsideEventReport
{
    use AsController;

    public function handle(?User $user, string $target, string $method, ?string $message = null): void
    {
        $eventPayload = [
            'app' => config('app.name'),
            'occurred_at' => now()->toIso8601String(),
            'user' => [
                'id' => $user?->id,
                'name' => $user?->name,
            ],
            'request' => [
                'endpoint' => $target,
                'method' => strtoupper($method),
            ],
            'message' => $message,
        ];

        Log::error('Error detected by frontend', $eventPayload);
    }

    public function asController(ActionRequest $request)
    {
        $data = $request->validate();

        $this->handle(
            user: $request->user(),
            target: $data['target'],
            method: $data['method'],
            message: $data['message'] ?? null,
        );

        return response()->noContent();
    }

    public function rules(): array
    {
        return [
            'target' => ['required', 'string', 'max:2048'],
            'method' => ['required', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:10240'],
        ];
    }
}
