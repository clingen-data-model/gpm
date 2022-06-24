<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Models\Coi;
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

    public function show($coiId)
    {
        $coi = Coi::find($coiId);
        return $coi;
    }
    

    public function getApplication($code)
    {
        $expertPanel = ExpertPanel::findByCoiCodeOrFail($code);

        return $expertPanel;
    }
}
