<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Coi;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CoiStorageRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Jobs\StoreCoiResponse;

class SimpleCoiController extends Controller
{

    public function __construct(private Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }



    public function getApplication($code)
    {
        $application = ExpertPanel::findByCoiCodeOrFail($code);

        return $application;
    }
    
    public function store($coiCode, CoiStorageRequest $request)
    {
        $job = new StoreCoiResponse($coiCode, $request->all());
        $this->dispatcher->dispatch($job);

        return response(['message' => 'COI response stored.'], 200);
    }

    public function getReport($coiCode)
    {
        $application = ExpertPanel::findByCoiCodeOrFail($coiCode);

        $coiDefinition = Coi::getDefinition();
        $questions = collect($coiDefinition->questions)->keyBy('name');
        $headings = $questions->map(function ($q) { return $q->question; });
        $headings[] = 'Date Completed';

        $coiData = Coi::forApplication($application)->get();

        $filename = Str::kebab($application->name).'-coi-report-'.Carbon::now()->format('Y-m-d').'.csv';
        $reportPath = '/tmp/'.$filename;
        $handle = fopen($reportPath, 'w');
        fputcsv($handle, $headings->toArray());
        $coiData->each(function ($coi) use ($handle, $questions) {
            $readableResponse = $coi->getResponseForHumans();
            $readableResponse['date_completed'] = $coi->created_at->format('Y-m-d H:i:s');
            fputcsv($handle, $readableResponse);
        });
        fclose($handle);


        return response()->download($reportPath, $filename);
    }
    
    
}
