<?php

use App\Modules\Group\Actions\MemberAdd;
use Illuminate\Support\Facades\Route;

// Route::post('/{{group_uuid}}/members', MemberAdd::class);


Route::group([
    'prefix' => 'api/groups',
    'middleware' => ['api']
], function () {
    Route::get('/', function () {
        return 'groups!';
    });
    
    Route::post('/{group_uuid}/members', MemberAdd::class);

    Route::group(['middleware' => ['auth:sanctum']], function () {
    });
});
