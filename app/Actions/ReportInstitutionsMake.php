<?php

namespace App\Actions;

use App\Modules\Person\Models\Institution;

class ReportInstitutionsMake extends ReportMakeAbstract
{
    public $commandSignature = 'reports:institutions';

    public function handle(): array
    {
        return Institution::has('people')
            ->withCount('people')
            ->orderBy('name')
            ->get()
            ->map(function ($c) {
                return [
                    'Name' => $c->name,
                    '# of People' => $c->people_count,
                ];
            })->toArray();
    }
}
