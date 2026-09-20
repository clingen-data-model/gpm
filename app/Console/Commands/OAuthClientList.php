<?php

namespace App\Console\Commands;

use Laravel\Passport\Passport;
use Illuminate\Console\Command;

/**
 * List OAuth clients (machine callers). Create them with
 * `passport:client --client --name="..."`, revoke with `oauth-client:revoke`.
 */
class OAuthClientList extends Command
{
    protected $signature = 'oauth-client:list {--all : Include revoked clients}';

    protected $description = 'List OAuth clients used for machine-to-machine access.';

    public function handle(): int
    {
        $query = Passport::client()->newQuery()->orderBy('created_at');
        if (! $this->option('all')) {
            $query->where('revoked', false);
        }

        $clients = $query->get();
        if ($clients->isEmpty()) {
            $this->info('No clients.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Grants', 'Revoked', 'Created', 'Active tokens'],
            $clients->map(fn ($c) => [
                $c->getKey(),
                $c->name,
                implode(',', (array) $c->grant_types),
                $c->revoked ? 'yes' : 'no',
                $c->created_at?->toDateString(),
                $c->tokens()->where('revoked', false)->where('expires_at', '>', now())->count(),
            ])->all()
        );

        return self::SUCCESS;
    }
}
