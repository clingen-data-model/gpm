<?php

namespace App\Modules\Person\Http\Controllers\Api;

use App\Modules\Foundation\Http\ApiController as BaseController;

class ApiController extends BaseController
{
    protected function getModelNamespace(): string
    {
        return 'App\\Modules\\Person\\Models\\';
    }

    protected function getResourceNamespace(): string
    {
        return 'App\\Modules\\Person\\Http\\Resources\\';
    }

    protected function getHiddenModels(): array
    {
        return [];
    }
}
