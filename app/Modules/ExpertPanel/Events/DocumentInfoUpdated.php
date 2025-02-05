<?php

namespace App\Modules\ExpertPanel\Events;

use App\Models\Document;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\InteractsWithSockets;

class DocumentInfoUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public Document $document)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        return 'Document ' . $this->document->type->name . ' version ' . $this->document->version . ' info updated';
    }

    public function getProperties(): array
    {
        return [
        ];
    }

    public function shouldPublish(): bool
    {
        return false;
    }

}
