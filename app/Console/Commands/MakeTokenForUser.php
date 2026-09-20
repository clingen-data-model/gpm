<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use App\Modules\User\Models\User;

class MakeTokenForUser extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user-token {email : email of user} {--expires-days=30 : Lifetime in days (0 = never)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Sanctum API token for a user, e.g. for local API testing (machines use OAuth clients)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::findByEmail($this->argument('email'));
        if (! $user) {
            $this->error('No user with that email.');

            return self::FAILURE;
        }
        $days = (int) $this->option('expires-days');
        $token = $user->createToken('api-token', ['*'], $days > 0 ? now()->addDays($days) : null);
        $this->line('Token created for user '.$user->name.' has value '.$token->plainTextToken);

        return self::SUCCESS;
    }
}
