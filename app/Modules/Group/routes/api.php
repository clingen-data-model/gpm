<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Actions\MemberAssignRole;

// Route::post('/{{group_uuid}}/members', MemberAdd::class);


Route::group([
    'prefix' => 'api/groups',
    'middleware' => ['api']
], function () {
    Route::get('/', function () {
        return 'groups!';
    });
    
    Route::post('/{group_uuid}/members', MemberAdd::class);

    Route::post('/{group_uuid}/members/{member_id}/roles', MemberAssignRole::class);

    Route::group(['middleware' => ['auth:sanctum']], function () {
    });
});
