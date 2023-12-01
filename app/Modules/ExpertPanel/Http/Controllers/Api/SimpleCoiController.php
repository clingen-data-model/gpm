<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Models\Group;
use Illuminate\Contracts\Bus\Dispatcher;

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

    public function getGroup($code)
    {
        return Group::findByCoiCodeOrFail($code);
    }

    public function getApplication($code)
    {
        return Group::findByCoiCodeOrFail($code)->expertPanel;
    }
}
