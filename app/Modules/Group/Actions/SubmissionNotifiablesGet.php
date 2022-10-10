<?php

namespace App\Modules\Group\Actions;

use App\Modules\User\Models\User;
use Illuminate\Support\Collection;

class SubmissionNotifiablesGet
{
    public function handle(Collection $except): Collection
    {
        return User::query()
                ->whereHas('permissions', function ($q) {
                    $q->whereIn('name', ['ep-applications-approve', 'ep-applications-comment']);
                })
                //->permission(['ep-applications-approve', 'ep-applications-comment'])
                ->with('person')
                ->whereDoesntHave('person', function($q) use ($except) {
                    $q->whereIn('id', $except);
                })
                ->get()
                ->pluck('person');
    }

}
