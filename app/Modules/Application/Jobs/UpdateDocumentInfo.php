<?php

namespace App\Modules\Application\Jobs;

use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Events\DocumentInfoUpdated;

class UpdateDocumentInfo
{
    use Dispatchable;

    private $application;
    private $document;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        String $applicationUuid, 
        String $uuid, 
        private String $dateReceived, 
        private String|null $dateReviewed = null)
    {
        $this->application = Application::findByUuidOrFail($applicationUuid);
        $this->document = Document::findByUuidOrFail($uuid);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->document->date_received = $this->dateReceived;
        $this->document->date_reviewed = $this->dateReviewed;

        if ($this->document->isDirty()) {
            DB::transaction(function () {
                $this->document->save();
                Event::dispatch(new DocumentInfoUpdated($this->application, $this->document));
            });
        }
    }
}
