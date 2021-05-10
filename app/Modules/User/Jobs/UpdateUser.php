<?php

namespace App\Modules\User\Jobs;

use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Modules\User\Events\UserCreated;
use App\Modules\User\Events\UserUpdated;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateUser
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private int $id, private string $name, private string $email)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Dispatcher $eventBus)
    {
        $user = User::find($this->id);

        $user->fill(['name' => $this->name, 'email' => $this->email]);

        if (!$user->isDirty()) {
            return $user;
        }

        $user->save();
        $eventBus->dispatch(new UserUpdated(user: $user));

        return $user;
    }
}
