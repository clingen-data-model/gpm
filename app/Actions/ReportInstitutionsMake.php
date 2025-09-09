<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\Person\Models\Institution;

class ReportInstitutionsMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:institutions';

    public function handle(): array
    {
        $rows = [];
        $this->streamRows(function (array $row) use (&$rows) { $rows[] = $row; });
        return $rows;
    }

    public function csvHeaders(): ?array
    {
        return ['Name', '# of People'];
    }

    public function streamRows(callable $push): void
    {
        DB::connection()->disableQueryLog();

        $this->baseQuery()
            ->orderBy('name')
            ->chunk(1000, function ($institutions) use ($push) {
                foreach ($institutions as $c) {
                    $push($this->formatRow($c));
                }
                $institutions->each->unsetRelations();
                gc_collect_cycles();
            });
    }

    private function baseQuery()
    {
        return Institution::query()
            ->has('people')
            ->select(['id','name'])
            ->withCount('people');
    }

    private function formatRow(Institution $c): array
    {
        return [
            'Name'        => $c->name,
            '# of People' => $c->people_count,
        ];
    }
}
