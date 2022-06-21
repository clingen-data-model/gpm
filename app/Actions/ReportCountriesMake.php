<?php

namespace App\Actions;
use Lorisleiva\Actions\ActionRequest;
use App\Modules\Person\Models\Country;
use Lorisleiva\Actions\Concerns\AsController;

class ReportCountriesMake extends ReportMakeAbstract
{

    public $commandSignature = 'reports:countries';
    public function handle(): array
    {
        return Country::has('people')
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