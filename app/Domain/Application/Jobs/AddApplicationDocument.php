<?php

namespace App\Domain\Application\Jobs;

use Carbon\Carbon;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Domain\Application\Models\Application;
use App\Models\Document;

class AddApplicationDocument
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private Application $application,
        private ?int $step_number = null,
        private string $uuid,
        private string $filename,
        private string $storage_path,
        private int $document_category_id,
        private int $version = 1,
        private ?Carbon $date_received = null,
        private ?Carbon $date_reviewed = null,
        private ?array $metadata = null,
    )
    {
        if (is_null($this->date_received)) {
            $this->date_received = Carbon::now();
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // dump($this->step_number);

        $document = Document::make([
            'uuid' => $this->uuid,
            'version' => 1,
            'filename' => $this->filename,
            'storage_path' => $this->storage_path,
            'document_category_id' => $this->document_category_id,
            'date_received' => $this->date_received,
            'date_reviewed' => $this->date_reviewed,
            'metadata' => $this->metadata,
            'step_number' => $this->step_number
        ]);
        
        $this->application->addDocument($document);

    }
}
