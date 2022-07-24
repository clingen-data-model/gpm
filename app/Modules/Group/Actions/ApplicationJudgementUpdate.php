<?php

namespace App\Modules\Group\Actions;
use Lorisleiva\Actions\ActionRequest;
    use Lorisleiva\Actions\Concerns\AsController;

class ApplicationJudgementUpdate
{
    use AsController;

    public function handle()
    {
        //
    }

    public function asController(ActionRequest $request)
    {
        // return $this->handle();
    }

    public function rules(ActionRequest $request): array
    {
        return [];
    }

    public function authorize(ActionRequest $request):bool
    {
        return true;
    }

}