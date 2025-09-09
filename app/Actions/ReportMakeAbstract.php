<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Actions\Utils\TransformArrayForCsv;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;

abstract class ReportMakeAbstract
{
    use AsController, AsCommand;

    public $commandSignature = null;

    public function __construct(private TransformArrayForCsv $csvTransformer) {}

    abstract public function handle();

    public function csvHeaders(): ?array { return null; }

    public function streamRows(callable $push): void
    {
        $data = $this->handle();
        if (is_iterable($data)) {
            foreach ($data as $row) { $push($row); }
        }
    }

    public function asController(ActionRequest $request)
    {
        if ($request->header('accept') === 'application/json') {
            return $this->handle();
        }

        return new StreamedResponse(function () {
            DB::connection()->disableQueryLog();
            $out = fopen('php://output', 'w');

            $headers = $this->csvHeaders();
            $wroteHeader = false;
            if ($headers) {
                fputcsv($out, $headers);
                $wroteHeader = true;
            }

            $this->streamRows(function (array $row) use ($out, &$wroteHeader) {
                foreach ($row as $k => $v) {
                    if (is_string($v)) $row[$k] = preg_replace('/\R/u', '; ', $v);
                }
                if (!$wroteHeader) { fputcsv($out, array_keys($row)); $wroteHeader = true; }
                fputcsv($out, $row);
            });

            fclose($out);
        }, 200, ['Content-Type' => 'text/csv']);
    }

    public function asCommand(Command $command)
    {
        DB::connection()->disableQueryLog();
        $out = fopen('php://output', 'w');

        $headers = $this->csvHeaders();
        $wroteHeader = false;
        if ($headers) {
            fputcsv($out, $headers);
            $wroteHeader = true;
        }

        $this->streamRows(function (array $row) use ($out, &$wroteHeader) {
            foreach ($row as $k => $v) {
                if (is_string($v)) $row[$k] = preg_replace('/\R/u', '; ', $v);
            }
            if (!$wroteHeader) { fputcsv($out, array_keys($row)); $wroteHeader = true; }
            fputcsv($out, $row);
        });

        fclose($out);
    }
}
