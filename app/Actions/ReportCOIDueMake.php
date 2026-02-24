<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\Person\Models\Person;

class ReportCOIDueMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:coi-due';

    public function csvHeaders(): ?array
    {
        return ['first_name','last_name','email','membership'];
    }

    public function streamRows(callable $push): void
    {
        DB::connection()->disableQueryLog();

        $this->baseQuery()
            ->orderBy('id')
            ->chunkById(1000, function ($people) use ($push) {
                foreach ($people as $p) {
                    $push($this->formatRow($p));
                }
                $people->each->unsetRelations();
                gc_collect_cycles();
            });
    }

    private function baseQuery()
    {
        return Person::query()
            ->isActivatedUser()
            ->hasPendingCois()
            ->select(['id','first_name','last_name','email'])
            ->with([
                'membershipsWithPendingCoi' => function ($q) {
                    $q->isActive()
                      ->select(['id','group_id','person_id'])
                      ->with(['group:id,name']);
                },
            ]);
    }

    private function formatRow(Person $p): array
    {
        $membership = $p->membershipsWithPendingCoi
            ->map(fn($m) => $m->group->name)
            ->filter()
            ->sort()
            ->values()
            ->implode('; ');

        return [
            'first_name' => $p->first_name,
            'last_name'  => $p->last_name,
            'email'      => $p->email,
            'membership' => $membership,
        ];
    }
}
