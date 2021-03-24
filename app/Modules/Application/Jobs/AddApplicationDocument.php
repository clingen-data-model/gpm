<?php

namespace App\Modules\Application\Jobs;

use Illuminate\Support\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Application\Models\Application;
use App\Models\Document;

class AddApplicationDocument
{
    use Dispatchable;

    private Application $application;
    private Carbon $dateReceived;
    private ?Carbon $dateReviewed;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $applicationUuid,
        private string $uuid,
        private string $filename,
        private string $storage_path,
        private int $document_type_id,
        private ?int $step = null,
        private ?string $date_received = null,
        private ?string $date_reviewed = null,
        private ?array $metadata = null,
        private ?bool $is_final = false,
    )
    {
        $this->application = Application::findByUuidOrFail($applicationUuid);
        $this->dateReceived = $date_received ? Carbon::parse($date_received) : Carbon::now();
        $this->dateReviewed = $date_reviewed ? Carbon::parse($date_reviewed) : null;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $document = Document::make([
            'uuid' => $this->uuid,
            'filename' => $this->filename,
            'storage_path' => $this->storage_path,
            'document_type_id' => $this->document_type_id,
            'date_received' => $this->dateReceived,
            'date_reviewed' => $this->dateReviewed,
            'metadata' => $this->metadata,
            'step' => $this->step,
            'is_final' => $this->is_final
        ]);
        
        $this->application->addDocument($document);

    }
}