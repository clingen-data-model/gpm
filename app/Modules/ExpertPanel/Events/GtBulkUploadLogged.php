<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Support\Carbon;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\PublishableApplicationEvent;
use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;

final class GtBulkUploadLogged extends ExpertPanelEvent implements PublishableApplicationEvent
{
    use IsPublishableApplicationEvent;

    public function __construct(
        public ExpertPanel $application,
        public string $status,
        public int $rowCount = 0,
        public ?string $message = null,
        public ?Carbon $at = null,
        public array $genes = [],
    ) {
        parent::__construct($application);
        $this->at ??= now();
    }

    public function getLogEntry(): string
    {
        $list = '';
        if (!empty($this->genes)) {
            $list  = ': '.implode(', ', $this->genes);
        }

        return $this->status === 'success' ? "GT bulk upload sent ({$this->rowCount} rows){$list}" : "GT bulk upload failed{$list}: {$this->message}";
    }

    public function getLogDate(): Carbon   { return $this->at; }
    public function getProperties(): array  {
        return ['status'=>$this->status,'rows'=>$this->rowCount,'message'=>$this->message,'genes'=>$this->genes];
    }

    public function shouldPublish(): bool
    {
        return false;
    }
}