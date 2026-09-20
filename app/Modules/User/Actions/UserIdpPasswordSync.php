<?php

namespace App\Modules\User\Actions;

use Throwable;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use App\Providers\IdpServiceProvider;
use Lorisleiva\Actions\Concerns\AsObject;
use App\Services\Idp\Contracts\IdpClient;

/**
 * Push a locally changed password to the IdP so both sign-in paths keep
 * accepting the same credential. Called from the three places a plaintext
 * password is available: Fortify's update and reset actions and the
 * user:change-password command. (Fortify's PasswordUpdatedViaController
 * and the framework's PasswordReset events carry only the user, which is
 * why this is not an event listener.)
 *
 * Never throws: a stale IdP password is recoverable, a failed local change
 * is not.
 */
class UserIdpPasswordSync
{
    use AsObject;

    public function __construct(private IdpClient $client)
    {
    }

    public function handle(User $user, string $plainPassword): bool
    {
        if (! IdpServiceProvider::enabled() || ! $user->isLinkedToIdp()) {
            return false;
        }

        try {
            $this->client->updatePassword($user->idp_id, $plainPassword);

            return true;
        } catch (Throwable $e) {
            Log::warning('Could not propagate password change to the identity provider.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            report($e);

            return false;
        }
    }
}
