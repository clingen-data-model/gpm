<?php
namespace App\Actions;

use App\Modules\Group\Models\GroupMember;
use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Concerns\AsAction;

class UpgradeForV2
{
    use AsAction;

    public string $commandSignature = 'v2:upgrade-data';

    public function handle()
    {
        dump('migrating...');
        Artisan::call('migrate', ['--no-interaction'=>true, '--force'=>true]);
        dump('seeding...');
        Artisan::call('db:seed', ['--no-interaction'=>true, '--force'=>true]);

        dump('making all contacts coordinators...');
        GroupMember::query()
            ->isContact()
            ->get()
            ->each(function ($member) {
                $member->assignRole('coordinator');
            });

        dump('link users to people...');
        Artisan::call('users:link-to-person');
        dump('create invites...');
        Artisan::call('people:create-invites');

        dump('Import VCEP Scopes...');
        Artisan::call('dev:import-cspec-scope');
    }
}
