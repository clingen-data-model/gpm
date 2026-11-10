<?php

namespace App\Actions\Auth;

use App\Auth\CILogon\CILogonIdpDirectory;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsController;

class CILogonIdpList
{
    use AsController;

    public function __construct(private CILogonIdpDirectory $directory)
    {
    }

    public function asController(Request $request)
    {
        abort_unless(config('services.cilogon.client_id'), 404);

        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return ['common' => $this->directory->common()];
        }

        return ['results' => mb_strlen($q) < 2 ? [] : $this->directory->search($q)];
    }
}
