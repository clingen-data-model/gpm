<?php

namespace App\Modules\ExpertPanel\Events;

use App\Models\Document;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\InteractsWithSockets;

class DocumentDeleted extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public Document $document)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        return 'Document deleted';
    }

    public function getProperties(): array
    {
        return ['document_uuid' => $this->document->uuid];
    }

    public function shouldPublish(): bool
    {
        return false;
    }

}
