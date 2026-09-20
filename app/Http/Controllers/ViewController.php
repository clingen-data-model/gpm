<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Providers\IdpServiceProvider;

class ViewController extends Controller
{
    public function app()
    {
        return view('app', ['idp' => static::idpConfig()]);
    }

    /**
     * Runtime identity-provider settings the SPA needs before any session
     * exists (the login page). Only public values belong here.
     */
    public static function idpConfig(): array
    {
        $driver = IdpServiceProvider::driver();

        return [
            'driver' => $driver,
            'clerk' => $driver === 'clerk'
                ? ['publishableKey' => (string) config('idp.clerk.publishable_key')]
                : null,
        ];
    }
}
