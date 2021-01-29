<?php

namespace App\Jobs\Pipes;

use Illuminate\Support\Facades\DB;

class UseDatabaseTransactions {

    public function handle($command, $next)
    {
        return DB::transaction(function() use ($command, $next)
        {
            return $next($command);
        });
    }

}