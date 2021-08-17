<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Carbon\Carbon;
use App\Modules\ExpertPanel\Models\Coi;
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
        $expertPanel = ExpertPanel::findByCoiCodeOrFail($code);

        return $expertPanel;
    }
    
    public function getReport($coiCode)
    {
        $expertPanel = ExpertPanel::findByCoiCodeOrFail($coiCode);

        $coiDefinition = Coi::getDefinition();
        $questions = collect($coiDefinition->questions)->keyBy('name');
        $headings = $questions->map(function ($q) { return $q->question; });
        $headings[] = 'Date Completed';

        $coiData = Coi::forApplication($expertPanel)->get();

        $filename = Str::kebab($expertPanel->name).'-coi-report-'.Carbon::now()->format('Y-m-d').'.csv';
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
