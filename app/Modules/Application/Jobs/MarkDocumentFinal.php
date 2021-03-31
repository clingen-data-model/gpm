<?php

namespace App\Modules\Application\Jobs;

use App\Models\Document;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;
use App\Modules\Application\Events\DocumentMarkedFinal;

class MarkDocumentFinal
{
    use Dispatchable;

    private Application $application;
    private Document $document;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $appUuid, string $documentUuid)
    {
        $this->application = Application::findByUuidOrFail($appUuid);
        $this->document = Document::findByUuidOrFail($documentUuid);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $prevFinal = $this->application
            ->documents()
            ->type($this->document->document_type_id)
            ->final()
            ->get();

        $prevFinal->each(function ($doc) {
                $doc->update(['is_final' => 0]);
            });

        $this->document->update(['is_final' => 1]);

        Event::dispatch(new DocumentMarkedFinal($this->application, $this->document));
    }
}
