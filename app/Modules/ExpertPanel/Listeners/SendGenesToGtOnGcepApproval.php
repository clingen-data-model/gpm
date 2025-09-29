<?php

namespace App\Modules\ExpertPanel\Listeners;

use Throwable;
use App\Services\Api\GtApiService;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\ExpertPanel\Events\GtBulkUploadLogged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class SendGenesToGtOnGcepApproval implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;
    public $tries = 1;
    public $backoff = [10, 60, 300];

    public function __construct(private GtApiService $gt) {}

    public function middleware(StepApproved $event): array
    {
        return [ new WithoutOverlapping("gt-bulkupload:{$event->application->uuid}:step:{$event->step}") ];
    }

    public function handle(StepApproved $event): void
    {
        $ep   = $event->application;
        $step = (int) $event->step;

        if (! $ep->is_gcep || $step !== 1) return;

        $rows  = $this->buildRows($ep);
        $nRows = count($rows);
        $genes = array_values(array_filter(array_column($rows, 'gene_symbol')));

        if (empty($rows)) {
            event(new GtBulkUploadLogged(
                application: $ep,
                status: 'success',
                rowCount: 0,
                message: 'No genes to send',
                genes: []
            ));
            Log::info('GT bulk upload skipped (no genes)', ['ep' => $ep->uuid]);
            return;
        }

        $payload = [
            'affiliation_id' => $ep->affiliation_id,
            'rows'            => $rows,
        ];

        $this->gt->approvalBulkUpload($payload);
        event(new GtBulkUploadLogged(
            application: $ep,
            status: 'success',
            rowCount: count($rows),
            message: $nRows . ' gene(s) sent to GT',
            genes: $genes
        ));
        Log::info('GT bulk upload sent', ['ep' => $ep->uuid, 'rows' => count($rows)]);
    }

    public function failed(StepApproved $event, \Throwable $e): void
    {
        $ep    = $event->application;
        $rows  = $this->buildRows($ep);
        $genes = array_values(array_filter(array_column($rows, 'gene_symbol')));

        event(new GtBulkUploadLogged(
            application: $ep,
            status: 'failed',
            rowCount: 0,
            message: $e->getMessage(),
            genes: $genes
        ));
        Log::error('GT bulk upload permanently failed', [ 'ep' => $ep->uuid, 'message' => $e->getMessage(), 'attempts' => $this->job?->attempts(), ]);
    }

    protected function buildRows(ExpertPanel $ep): array
    {
        $ep->loadMissing('genes');
        $dateUploaded = $ep->step_1_approval_date?->toDateString();
        return $ep->genes
            ->map(function ($g) use ($dateUploaded) {
                $symbol = strtoupper(trim((string) $g->gene_symbol));
                if ($symbol === '') { return null; }
                return [
                    'gene_symbol' => $symbol,
                    'curator_email' => '',
                    'curation_type' => '',
                    'mondo_id' => '',
                    'disease_entity_if_there_is_no_mondo_id' => '',
                    'rationale_notes' => '',
                    'date_uploaded' => $dateUploaded,
                ];
            })
            ->filter()->values()->all();
    }
}