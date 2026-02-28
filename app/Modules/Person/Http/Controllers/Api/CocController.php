<?php

namespace App\Modules\Person\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CocService;
use Illuminate\Http\Request;

class CocController extends Controller
{
    public function show(Request $request, CocService $coc)
    {
        $person = $request->user()->person;

        if (!$person) {
            abort(404, 'Person not found.');
        }

        $person->loadMissing('latestCocAttestation');

        return [
            'status'  => $coc->statusFor($person),
            'content' => $coc->renderContent(),
        ];
    }
}
