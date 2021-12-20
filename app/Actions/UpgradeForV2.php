<?php
namespace App\Actions;

use Illuminate\Support\Facades\Artisan;
use Lorisleiva\Actions\Concerns\AsAction;

class UpgradeForV2
{
    use AsAction;

    public string $commandSignature = 'v2:upgrade-data';

    public function handle()
    {
        Artisan::call('migrate');
        Artisan::call('db:seed');
        Artisan::call('users:link-to-person');
        Artisan::call('people:create-invites');
    }
}
