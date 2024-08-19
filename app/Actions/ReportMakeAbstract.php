<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Actions\Utils\TransformArrayForCsv;
use Lorisleiva\Actions\Concerns\AsController;

abstract class ReportMakeAbstract
{
    use AsController, AsCommand;

    public $commandSignature = null;

    public function __construct(private TransformArrayForCsv $csvTransformer)
    {
    }

    abstract public function handle(): array;

    public function asController(ActionRequest $request)
    {
        $data = $this->handle();

        if (!is_array($data)) {
            throw new \Exception('Expected data to be an array, got: ' . gettype($data));
        }

        if ($request->header('accept') == 'application/json') {
            return response()->json($data);
        }

        $csvData = $this->csvTransformer->handle($data);
        return response($csvData, 200, ['Content-Type' => 'text/csv']);
    }

    public function asCommand(Command $command)
    {
        $data = $this->handle();

        if (!is_array($data)) {
            throw new \Exception('Expected data to be an array, got: ' . gettype($data));
        }

        $csvData = $this->csvTransformer->handle($data);
        $command->info($csvData);
    }
}
