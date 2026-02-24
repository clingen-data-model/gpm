<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\Person\Models\Country;

class ReportCountriesMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:countries';

    public function csvHeaders(): ?array
    {
        return ['Name', '# of People'];
    }

    public function streamRows(callable $push): void
    {
        $connection = DB::connection();
        $queryLogEnabled = $connection->logging();
        $connection->disableQueryLog();
        try {
            $this->baseQuery()
                ->orderBy('name')
                ->chunk(1000, function ($countries) use ($push) {
                    foreach ($countries as $c) {
                        $push($this->formatRow($c));
                    }
                    $countries->each->unsetRelations();
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
        return Country::query()
            ->has('people')
            ->select(['id','name'])
            ->withCount('people');
    }

    private function formatRow(Country $c): array
    {
        return [
            'Name'        => $c->name,
            '# of People' => $c->people_count,
        ];
    }
}
