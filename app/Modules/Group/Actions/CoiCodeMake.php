<?php

namespace App\Modules\Group\Actions;


class CoiCodeMake
{

    public function handle()
    {
        return bin2hex(random_bytes(12));
    }
}
