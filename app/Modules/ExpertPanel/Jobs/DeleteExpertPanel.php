<?php

namespace App\Modules\ExpertPanel\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\ExpertPanelDeleted;

class DeleteExpertPanel
{
    use Dispatchable;

    private ExpertPanel  $expertPanel;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(String $expertPanelUuid)
    {
        $this->expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->expertPanel->delete();

        event(new ExpertPanelDeleted(application: $this->expertPanel));
    }
}
