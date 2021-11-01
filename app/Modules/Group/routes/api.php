<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Group\Actions\MemberAdd;
use App\Modules\Group\Actions\GeneRemove;
use App\Modules\Group\Actions\GenesAdd;
use App\Modules\Group\Actions\MemberInvite;
use App\Modules\Group\Actions\MemberRemove;
use App\Modules\Group\Actions\MemberRetire;
use App\Modules\Group\Actions\MemberUpdate;
use App\Modules\Group\Actions\AddGenesToVcep;
use App\Modules\Group\Actions\MemberAssignRole;
use App\Modules\Group\Actions\MemberRemoveRole;
use App\Modules\Group\Actions\MemberGrantPermissions;
use App\Modules\Group\Actions\MemberRevokePermission;
use App\Modules\Group\Actions\ScopeDescriptionUpdate;
use App\Modules\Group\Actions\MembershipDescriptionUpdate;
use App\Modules\Group\Http\Controllers\Api\GroupController;

// Route::post('/{{group_uuid}}/members', MemberAdd::class);



Route::group([
    'prefix' => 'api/groups',
    'middleware' => ['api', 'auth:sanctum']
], function () {
    Route::get('/', [GroupController::class, 'index']);
    Route::get('/{uuid}', [GroupController::class, 'show']);

    Route::put('/{uuid}/application/membership-description', MembershipDescriptionUpdate::class);
    Route::put('/{uuid}/application/scope-description', ScopeDescriptionUpdate::class);
    Route::post('/{uuid}/application/genes', GenesAdd::class);
    Route::delete('/{uuid}/application/genes/{gene_id}', GeneRemove::class);
    
    Route::post('/{uuid}/invites', MemberInvite::class);
    
    Route::post('/{group_uuid}/members', MemberAdd::class);
    Route::delete('/{group_uuid}/members/{member_id}', MemberRemove::class);
    Route::put('/{group_uuid}/members/{member_id}', MemberUpdate::class);
    Route::post('/{group_uuid}/members/{member_id}/retire', MemberRetire::class);
    
    Route::post('/{group_uuid}/members/{member_id}/roles', MemberAssignRole::class);
    Route::delete('/{group_uuid}/members/{member_id}/roles/{role_id}', MemberRemoveRole::class);

    Route::post('/{group_uuid}/members/{member_id}/permissions', MemberGrantPermissions::class);
    Route::delete('/{group_uuid}/members/{member_id}/permissions/{permission_id}', MemberRevokePermission::class);
});
