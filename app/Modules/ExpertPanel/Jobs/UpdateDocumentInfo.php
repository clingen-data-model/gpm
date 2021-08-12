<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Models\Document;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\DocumentInfoUpdated;

class UpdateDocumentInfo
{
    use Dispatchable;

    private $expertPanel;
    private $document;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        String $expertPanelUuid,
        String $uuid,
        private String $dateReceived,
        private string|null $notes = null
    ) {
        $this->expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
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
        $this->document->notes = $this->notes;

        if ($this->document->isDirty()) {
            DB::transaction(function () {
                $this->document->save();
                Event::dispatch(new DocumentInfoUpdated($this->expertPanel, $this->document));
            });
        }
    }
}
