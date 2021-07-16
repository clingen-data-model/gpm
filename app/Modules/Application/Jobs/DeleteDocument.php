<?php

namespace App\Modules\Application\Jobs;

use App\Models\Document;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Events\DocumentDeleted;

class DeleteDocument
{
    use Dispatchable;

    protected Application $application;

    protected Document $document;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $applicationUuid, string $documentUuid)
    {
        $this->application= Application::findByUuidOrFail($applicationUuid);
        $this->document = $this->application->documents()->where('uuid', $documentUuid)->sole();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->document->delete();

        event(new DocumentDeleted($this->application, $this->document));
    }
}
