<?php

namespace App\Jobs;

use App\Modules\Group\Models\Publication;
use App\Modules\Group\Service\RemotePublicationClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EnrichPublication implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 5;
    public int $backoff = 30;

    public function __construct(public int $publicationId) {}

    public function handle(RemotePublicationClient $client): void
    {
        $pub = Publication::find($this->publicationId);
        if (!$pub) return;

        $pub->status = 'enriching';
        $pub->error  = null;
        $pub->save();

        try {
            $meta = (array) $client->fetch($pub->source, $pub->identifier);
            $meta['url'] = $client->extractUrl($meta);

            $pub->meta         = $meta;
            $pub->published_at = $client->extractDate($meta);
            $pub->pub_type     = $client->extractType($meta);
            $pub->status       = 'enriched';
            $pub->error        = null;
        } catch (\Throwable $e) {
            $pub->status = 'failed';
            $pub->error  = substr($e->getMessage(), 0, 1000);
            throw $e; 
        }

        $pub->save();
    }
}
