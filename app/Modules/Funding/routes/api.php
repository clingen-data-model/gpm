<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Funding\Http\Controllers\Api\FundingSourceController;
use App\Modules\Funding\Actions\FundingSourceCreate;
use App\Modules\Funding\Actions\FundingSourceUpdate;
use App\Modules\Funding\Actions\FundingSourceDelete;
use App\Modules\Funding\Http\Controllers\Api\FundingTypeController;

Route::group([
    'prefix' => 'api/funding-sources',
    'middleware' => ['api', 'auth:sanctum'],
], function () {
    Route::get('/', [FundingSourceController::class, 'index']);
    Route::get('/{fundingSource}', [FundingSourceController::class, 'show']);

    Route::post('/', FundingSourceCreate::class);
    Route::post('/{fundingSource}', FundingSourceUpdate::class); // using POST for multipart updates (like many apps do)
    Route::delete('/{fundingSource}', FundingSourceDelete::class);
});

Route::group([
    'prefix' => 'api/funding-types',
    'middleware' => ['api', 'auth:sanctum'],
], function () {
    Route::get('/', [FundingTypeController::class, 'index']);
});