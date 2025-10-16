<?php

namespace App\Jobs;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;
use App\Modules\Group\Events\PublicationAdded;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPublicationAdded implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 5;
    public int $backoff = 30;

    public function __construct(
        public int $groupId,
        public int $publicationId
    ) {}

    public function handle(): void
    {
        $pub = Publication::find($this->publicationId);
        if (!$pub) return;

        if ($pub->status !== 'enriched' || empty($pub->meta['title'] ?? null)) {
            $this->release($this->backoff);
            return;
        }

        $group = Group::find($this->groupId);
        if (!$group) return;

        if ($pub->sent_to_dx_at) return;

        event(new PublicationAdded($group, $pub->fresh()));

        $pub->forceFill(['sent_to_dx_at' => now()])->save();
    }
}
