<?php

namespace App\Actions;

use Illuminate\Console\Command;
use Lorisleiva\Actions\ActionRequest;
use Lorisleiva\Actions\Concerns\AsCommand;
use App\Actions\Utils\TransformArrayForCsv;
use Lorisleiva\Actions\Concerns\AsController;

abstract class ReportMakeAbstract
{
    use AsController;
    use AsCommand;

    public $commandSignature = null;

    public function __construct(private TransformArrayForCsv $csvTransformer)
    {
    }

    abstract public function handle();

    public function asController(ActionRequest $request)
    {
        $data = $this->handle();

        if ($request->header('accept') == 'application/json') {
            return $data;
        }

        $data = $this->csvTransformer->handle($this->handle());
        return response($data, 200, ['Content-type' => 'text/csv']);
    }

    public function asCommand(Command $command)
    {
        $command->info($this->csvTransformer->handle($this->handle()));
    }
}