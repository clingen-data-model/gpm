<?php

namespace App\Modules\ExpertPanel\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class UpdateExpertPanelAttributes
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $uuid, 
        private array $attributes,
    )
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $application = ExpertPanel::findByUuidOrFail($this->uuid);
        $application->setExpertPanelAttributes($this->attributes);
    }
}
