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

    public function csvHeaders(): ?array { return null; }

    abstract public function streamRows(callable $push): void;

    private function outputCsv($out): void
    {
        // Help Excel detect UTF-8 characters
        fwrite($out, "\xEF\xBB\xBF");
        $headers = $this->csvHeaders();
        $wroteHeader = false;
        if ($headers) {
            fputcsv($out, $headers);
            $wroteHeader = true;
        }

        $this->streamRows(function (array $row) use ($out, &$wroteHeader) {
            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $row[$key] = preg_replace('/\R/u', '; ', $value);
                } elseif (is_array($value)) {
                    $row[$key] = json_encode(
                        $value,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
                } elseif (is_bool($value)) {
                    $row[$key] = $value ? '1' : '0';
                } elseif ($value === null) {
                    $row[$key] = '';
                }
            }
            if (!$wroteHeader) {
                fputcsv($out, array_keys($row));
                $wroteHeader = true;
            }
            fputcsv($out, $row);
        });
    }

    public function asController(ActionRequest $request)
    {
        if ($request->header('accept') === 'application/json') {
            return response()->stream(function () {
                echo '[';
                $first = true;
                $this->streamRows(function (array $row) use (&$first) {
                    if (!$first) {
                        echo ',';
                    }

                    echo json_encode(
                        $row,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    );
                    flush();
                    $first = false;
                });
                echo ']';
            }, 200, ['Content-Type' => 'application/json; charset=UTF-8']);
        }

        return new StreamedResponse(function () {
            $out = fopen('php://output', 'w');
            $this->outputCsv($out);
            fclose($out);
        }, 200, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function asCommand(Command $command)
    {
        $out = fopen('php://output', 'w');
        $this->outputCsv($out);
        fclose($out);
    }
}
