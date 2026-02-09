<?php

namespace App\Modules\Person\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CocService;
use Illuminate\Http\Request;

class CocController extends Controller
{
    public function show(Request $request, CocService $coc)
    {
        $person = $request->user()->person ?? $request->user();

        $person->loadMissing('latestCocAttestation');

        return [
            'status'  => $coc->statusFor($person),
            'content' => $coc->renderContent(),
        ];
    }
}
