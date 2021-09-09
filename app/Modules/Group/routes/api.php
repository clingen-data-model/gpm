<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Actions\MemberRetire;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Actions\MemberRemoveRole;

// Route::post('/{{group_uuid}}/members', MemberAdd::class);


Route::group([
    'prefix' => 'api/groups',
    'middleware' => ['api']
], function () {
    Route::get('/', function () {
        return 'groups!';
    });
    
    Route::post('/{group_uuid}/members', MemberAdd::class);
    Route::delete('/{group_uuid}/members/{member_id}', MemberRetire::class);

    Route::post('/{group_uuid}/members/{member_id}/roles', MemberAssignRole::class);
    Route::delete('/{group_uuid}/members/{member_id}/roles/{role_id}', MemberRemoveRole::class);

    Route::group(['middleware' => ['auth:sanctum']], function () {
    });
});
