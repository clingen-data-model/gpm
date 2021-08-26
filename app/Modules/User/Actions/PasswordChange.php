<?php

namespace App\Modules\User\Actions;

use Illuminate\Console\Command;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsObject;
use Lorisleiva\Actions\Concerns\AsCommand;

class PasswordChange
{
    use AsObject;
    use AsCommand;

    public string $commandSignature = 'user:change-password {user_id}';
    public string $commandDescription = 'Changes a user password given uuid, numeric id, or email address';

    public function handle($user, $newPassword): User
    {
        if (!$user instanceof User) {
            if (is_string($user)) {
                $user = User::findByEmail($user);
                if (!$user) {
                    $user = User::findByUuid($user);
                }
            }
            if (is_int($user)) {
                $user = User::find($user);
            }
        }

        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        return $user;
    }

    public function asCommand(Command $command): void
    {
        $newPassword = $command->secret('New password');
        if (empty($newPassword)) {
            $command->error('You must enter a password.');
            return;
        }

        $user = $this->handle($command->argument('user_id'), $newPassword);
        $command->info('Password updated for user '.$user->email);
    }
}
