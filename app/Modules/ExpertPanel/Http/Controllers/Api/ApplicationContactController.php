<?php

namespace App\Modules\ExpertPanel\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Contracts\Bus\Dispatcher;

class ApplicationContactController extends Controller
{
    public function __construct(private Dispatcher $dispatcher)
    {
    }

    public function index($expertPanelUuid)
    {
        $expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);

        return $expertPanel
            ->contacts()
            ->with('person')
            ->get()
            ->pluck('person');
    }
}
