<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\Person\Models\Person;

class ReportMultipleEpsMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:multiple-eps';

    public function handle(): array
    {
        $rows = [];
        $this->streamRows(function (array $row) use (&$rows) { $rows[] = $row; });
        return $rows;
    }

    public function csvHeaders(): ?array
    {
        return ['first_name','last_name','email','ep count'];
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
