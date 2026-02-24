<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class ReportMakeAbstract
{
    use AsController, AsCommand;

    public $commandSignature = null;

    abstract public function handle();

    public function csvHeaders(): ?array { return null; }

    public function streamRows(callable $push): void
    {
        $data = $this->handle();
        if (is_iterable($data)) {
            foreach ($data as $row) { $push($row); }
        }
    }

    private function outputCsv($out) {
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
    }

    public function asController(ActionRequest $request)
    {
        if ($request->header('accept') === 'application/json') {
            return $this->handle();
        }

        return new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            $this->outputCsv($out);
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv']);
    }

    public function asCommand(Command $command)
    {
        $out = fopen('php://output', 'w');
        $this->outputCsv($out);
        fclose($out);
    }
}
