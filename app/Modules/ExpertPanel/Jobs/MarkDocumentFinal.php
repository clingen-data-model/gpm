<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Models\Document;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\DocumentMarkedFinal;

class MarkDocumentFinal
{
    use Dispatchable;

    private ExpertPanel  $expertPanel;
    private Document $document;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $appUuid, string $documentUuid)
    {
        $this->expertPanel = ExpertPanel::findByUuidOrFail($appUuid);
        $this->document = Document::findByUuidOrFail($documentUuid);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $prevFinal = $this->expertPanel
            ->documents()
            ->type($this->document->document_type_id)
            ->final()
            ->get();

        $prevFinal->each(function ($doc) {
                $doc->update(['is_final' => 0]);
            });

        $this->document->update(['is_final' => 1]);

        Event::dispatch(new DocumentMarkedFinal($this->expertPanel, $this->document));
    }
}
