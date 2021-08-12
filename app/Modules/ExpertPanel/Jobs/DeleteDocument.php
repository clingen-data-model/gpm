<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Models\Document;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Events\DocumentDeleted;

class DeleteDocument
{
    use Dispatchable;

    protected ExpertPanel  $expertPanel;

    protected Document $document;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $expertPanelUuid, string $documentUuid)
    {
        $this->expertPanel= ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $this->document = $this->expertPanel->documents()->where('uuid', $documentUuid)->sole();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->document->delete();

        event(new DocumentDeleted($this->expertPanel, $this->document));
    }
}