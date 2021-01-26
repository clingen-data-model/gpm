<?php

namespace App\Domain\Application\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use App\Domain\Application\Models\Application;
use App\Models\Document;
use Carbon\Carbon;

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
        $application = Application::findByUuidOrFail($this->applicationUuid);
        $document = Document::findByUuidOrFail($this->documentUuid);
        $application->markDocumentReviewed($document, $this->dateReviewed);
    }
}
