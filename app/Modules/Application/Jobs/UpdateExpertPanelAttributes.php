<?php

namespace App\Modules\Application\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;

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
        $application = Application::findByUuidOrFail($this->uuid);
        $application->setExpertPanelAttributes($this->attributes);
    }
}
