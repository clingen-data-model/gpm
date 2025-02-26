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
    protected $signature = 'make:user-token {email : email of user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a sanctum API token for a user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = User::findByEmail($this->argument('email'));
        $token = $user->createToken('api-token');
        $this->line('Token created for user '.$user->name.' has value '.$token->plainTextToken);
    }
}
