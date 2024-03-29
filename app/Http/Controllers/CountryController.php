<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Modules\Person\Models\Country;

class CountryController extends Controller
{
    public function index()
    {
        $countries = Country::orderBy('name')->get();

        return $countries->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]);
    }
}
