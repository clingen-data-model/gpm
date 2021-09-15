<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Actions\MemberInvite;
use App\Modules\Group\Actions\MemberRemove;
use App\Modules\Group\Actions\MemberRetire;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Actions\MemberRemoveRole;
use App\Modules\Group\Actions\MemberGrantPermissions;
use App\Modules\Group\Actions\MemberRevokePermission;

// Route::post('/{{group_uuid}}/members', MemberAdd::class);



Route::group([
    'prefix' => 'api/groups',
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::get('/{group_uuid}/fucks', function ($uuid) {
        return 'what the actual fuck: '.$uuid;
    });
    
    Route::post('/{uuid}/invites', MemberInvite::class);
    
    Route::post('/{group_uuid}/members', MemberAdd::class);
    Route::delete('/{group_uuid}/members/{member_id}', MemberRemove::class);
    Route::post('/{group_uuid}/members/{member_id}/retire', MemberRetire::class);
    
    Route::post('/{group_uuid}/members/{member_id}/roles', MemberAssignRole::class);
    Route::delete('/{group_uuid}/members/{member_id}/roles/{role_id}', MemberRemoveRole::class);

    Route::post('/{group_uuid}/members/{member_id}/permissions', MemberGrantPermissions::class);
    Route::delete('/{group_uuid}/members/{member_id}/permissions/{permission_id}', MemberRevokePermission::class);

    Route::get('/', function () {
        return 'groups!';
    });
});
