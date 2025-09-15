<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsCommand;
use Lorisleiva\Actions\Concerns\AsController;
use Symfony\Component\HttpFoundation\StreamedJsonResponse;

abstract class ReportMakeAbstract
{
    use AsController;
    use AsCommand;

    public $commandSignature = null;

    abstract public function handle();

    public function asController(ActionRequest $request)
    {
        $data = $this->handle();

        if ($request->header('accept') == 'application/json') {
            return new StreamedJsonResponse($data);
        }

        return new StreamedResponse(function () use ($data) {
            $handle = fopen('php://output', 'w');
            $rowCount = 0;
            foreach ($data as $row) {
                if ($rowCount == 0) {
                    // only print header once
                    fputcsv($handle, array_keys($row));
                }
                $rowCount++;
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="report.csv"',
        ]);
    }

    public function asCommand(Command $command)
    {
        $command->info($this->csvTransformer->handle($this->handle()));
    }
}
