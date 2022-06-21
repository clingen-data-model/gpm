<?php

namespace App\Actions;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Person\Models\Institution;
use Lorisleiva\Actions\Concerns\AsController;

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
                        '# of People' => $c->people_count
                    ];
                })->toArray();
    }

}