<?php

namespace App\Modules\ExpertPanel\Jobs;

use Illuminate\Support\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Models\Document;

class ApplicationDocumentAdd
{
    use Dispatchable;

    private ExpertPanel  $expertPanel;
    private Carbon $dateReceived;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $expertPanelUuid,
        private string $uuid,
        private string $filename,
        private string $storage_path,
        private int $document_type_id,
        private ?int $step = null,
        private ?string $date_received = null,
        private ?array $metadata = null,
        private ?bool $is_final = false,
        private ?string $notes = null
    ) {
        $this->expertPanel = ExpertPanel::findByUuidOrFail($expertPanelUuid);
        $this->dateReceived = $date_received ? Carbon::parse($date_received) : Carbon::now();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $document = new Document([
            'uuid' => $this->uuid,
            'filename' => $this->filename,
            'storage_path' => $this->storage_path,
            'document_type_id' => $this->document_type_id,
            'date_received' => $this->dateReceived,
            'metadata' => $this->metadata,
            'step' => $this->step,
            'is_final' => $this->is_final,
            'notes' => $this->notes
        ]);
        
        $this->expertPanel->addDocument($document);
    }
}
