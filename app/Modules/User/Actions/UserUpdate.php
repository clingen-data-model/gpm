<?php

namespace App\Modules\User\Actions;

use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Event;
use App\Modules\User\Events\UserUpdated;
use Lorisleiva\Actions\Concerns\AsAction;
use Illuminate\Contracts\Events\Dispatcher;

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
     *
     * @return void
     */
    public function handle(int $id, string $name, string $email)
    {
        $user = User::find($id);

        $user->fill(['name' => $name, 'email' => $email]);

        if (!$user->isDirty()) {
            return $user;
        }

        $user->save();
        Event::dispatch(new UserUpdated(user: $user));

        return $user;
    }
}
