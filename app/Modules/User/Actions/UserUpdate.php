<?php

namespace App\Modules\User\Actions;

use App\Modules\User\Events\UserUpdated;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class UserUpdate
{
    use AsAction;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(int $id, string $name, string $email): void
    {
        $user = User::find($id);

        $user->fill(['name' => $name, 'email' => $email]);

        if (! $user->isDirty()) {
            return $user;
        }

        $user->save();
        Event::dispatch(new UserUpdated(user: $user));

        return $user;
    }
}
