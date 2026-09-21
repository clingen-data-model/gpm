<?php

namespace App\Modules\User\Actions;

use App\Services\Idp\IdpUser;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Modules\User\Events\UserUpdated;
use Lorisleiva\Actions\Concerns\AsObject;

/**
 * Refresh the local login record from the IdP profile. Only users.name and
 * users.email are synced; the Person record is GPM's own contact data.
 */
class UserIdpProfileSync
{
    use AsObject;

    public function handle(User $user, IdpUser $idpUser): User
    {
        $name = $idpUser->name();
        if ($name !== null && $name !== $user->name) {
            $user->name = $name;
        }

        $email = $idpUser->email;
        if ($email !== null && mb_strtolower($email) !== mb_strtolower((string) $user->email)) {
            $owner = User::whereEmailInsensitive($email)->where('id', '!=', $user->id)->first();
            if ($owner) {
                Log::warning('Skipped IdP email sync: address already belongs to another user.', [
                    'user_id' => $user->id,
                    'other_user_id' => $owner->id,
                ]);
            } else {
                $user->email = $email;
            }
        }

        if (! $user->isDirty()) {
            return $user;
        }

        $user->save();
        Event::dispatch(new UserUpdated(user: $user));

        return $user;
    }
}
