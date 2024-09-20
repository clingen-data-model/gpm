<?php

namespace App\Actions;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsController;

class ReportCOIDueMake extends ReportMakeAbstract
{

    public $commandSignature = 'reports:coi-due';
    public function handle(): array
    {
        return Person::query()
            ->isActivatedUser()
            ->hasPendingCois()
            ->with('membershipsWithPendingCoi', 'membershipsWithPendingCoi.group')
            ->get()
            ->map(function($p) {
                return [
                    'first_name' => $p->first_name,
                    'last_name' => $p->last_name,
                    'email' => $p->email,
                    'membership' => $p->membershipsWithPendingCoi->map(function ($m) { return $m->group->name; })->sort()->values()
                ];
        })
        ->toArray();
    }
}
