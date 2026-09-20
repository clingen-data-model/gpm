<?php

namespace App\Console\Commands;

use Laravel\Passport\Passport;
use Illuminate\Console\Command;

/**
 * Revoke an OAuth client and every token it has been issued. Passport only
 * refuses new tokens for a revoked client; outstanding ones would otherwise
 * stay valid until they expire.
 */
class OAuthClientRevoke extends Command
{
    protected $signature = 'oauth-client:revoke {id : Client id (see oauth-client:list)}';

    protected $description = 'Revoke an OAuth client and its outstanding tokens.';

    public function handle(): int
    {
        $client = Passport::client()->newQuery()->find($this->argument('id'));
        if (! $client) {
            $this->error('No client with that id.');

            return self::FAILURE;
        }

        $tokens = $client->tokens()->where('revoked', false)->update(['revoked' => true]);
        $client->forceFill(['revoked' => true])->save();

        $this->info("Revoked client '{$client->name}' ({$client->getKey()}) and {$tokens} outstanding token(s).");

        return self::SUCCESS;
    }
}
