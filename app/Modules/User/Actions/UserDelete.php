<?php

namespace App\Modules\User\Actions;

use App\Modules\User\Models\User;
use App\Modules\User\Events\UserDeleted;
use Lorisleiva\Actions\Concerns\AsAction;

class UserDelete
{
    use AsAction;

    public function handle(User $user): void
    {
        $user->roles->each->delete();
        $user->permissions->each->delete();
        $user->delete();

        event(new UserDeleted($user));
    }
}
