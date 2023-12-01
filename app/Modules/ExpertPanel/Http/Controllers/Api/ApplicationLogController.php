<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class ApplicationLogController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function index($expertPanelUuid, Request $request)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);

        return $expertPanel->logEntries()->with([
            'causer' => function (MorphTo $morphTo) {
            },
        ])->get();
    }
}
