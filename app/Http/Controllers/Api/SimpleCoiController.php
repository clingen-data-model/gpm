<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CoiStorageRequest;
use Illuminate\Contracts\Bus\Dispatcher;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Jobs\StoreCoiResponse;

class SimpleCoiController extends Controller
{

    public function __construct(private Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }



    public function getApplication($code)
    {
        $application = Application::findByCoiCodeOrFail($code);

        return $application;
    }
    
    public function store($coiCode, CoiStorageRequest $request)
    {
        $job = new StoreCoiResponse($coiCode, $request->all());
        $this->dispatcher->dispatch($job);

        return response(['message' => 'COI response stored.'], 200);
    }
    
}
