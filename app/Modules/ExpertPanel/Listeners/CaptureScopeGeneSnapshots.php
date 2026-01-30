<?php 
namespace App\Modules\ExpertPanel\Listeners;

use App\Modules\Group\Events\ApplicationStepSubmitted;
use App\Modules\ExpertPanel\Actions\GcepCaptureScopeGeneSnapshots;
use App\Modules\ExpertPanel\Actions\VcepCaptureScopeGeneSnapshots;
use Illuminate\Support\Facades\Log;

class CaptureScopeGeneSnapshots
{
    public function handle(ApplicationStepSubmitted $event): void
    {
        $group = $event->group;
        if (! $group->is_ep) { return; }
        if ($group->is_gcep) app()->make(GcepCaptureScopeGeneSnapshots::class)->handle($group);
        if ($group->is_vcep) app()->make(VcepCaptureScopeGeneSnapshots::class)->handle($group);
    }
}
