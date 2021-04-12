<?php

namespace App\Modules\Application\Jobs;

use App\Models\Document;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;

class MarkDocumentReviewed
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $applicationUuid, 
        private string $documentUuid, 
        private Carbon $dateReviewed
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
        Log::warning('This command has been deprecated in the MVP', backtrace());
        $application = Application::findByUuidOrFail($this->applicationUuid);
        $document = Document::findByUuidOrFail($this->documentUuid);
        $application->markDocumentReviewed($document, $this->dateReviewed);
    }
}
