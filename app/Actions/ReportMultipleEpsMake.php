<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\Person\Models\Person;

class ReportMultipleEpsMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:multiple-eps';

    public function csvHeaders(): ?array
    {
        return ['first_name','last_name','email','ep count'];
    }

    public function streamRows(callable $push): void
    {
        $connection = DB::connection();
        $queryLogEnabled = $connection->logging();
        $connection->disableQueryLog();
        try {
            $this->baseQuery()
                ->orderBy('id')
                ->chunkById(1000, function ($people) use ($push) {
                    foreach ($people as $p) {
                        $push($this->formatRow($p));
                    }
                    $people->each->unsetRelations();
                    gc_collect_cycles();
                });
        } finally {
            if ($queryLogEnabled) {
                $connection->enableQueryLog();
            }
        }
    }

    private function baseQuery()
    {
        return Person::query()
            ->has('activeExpertPanels', '>=', 2)
            ->select(['id','first_name','last_name','email'])
            ->with([
                'activeExpertPanels' => function ($q) {
                    $q->select(['groups.id','group_type_id','name']);
                },
            ]);
    }

    private function formatRow(Person $p): array
    {
        return [
            'first_name' => $p->first_name,
            'last_name'  => $p->last_name,
            'email'      => $p->email,
            'ep count'   => $p->activeExpertPanels->count(),
        ];
    }
}
