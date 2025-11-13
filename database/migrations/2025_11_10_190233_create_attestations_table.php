<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attestations', function (Blueprint $table) {
            $table->uuid('uuid')->primary();

            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->json('experience_types')->nullable();
            $table->text('other_text')->nullable();

            $table->string('attestation_version')->nullable();
            $table->foreignId('attested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('attested_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Uniqueness: one active per person (not revoked, not soft-deleted)
            $table->unsignedBigInteger('active_person_id')->nullable()->storedAs('IF(`revoked_at` IS NULL AND `deleted_at` IS NULL, `person_id`, NULL)');
            $table->unique('active_person_id', 'uniq_active_attestation_per_person');
            
            $table->index(['person_id', 'attestation_version']);
            $table->index('attested_at');
            $table->index('revoked_at');
            $table->index('deleted_at');
        });

        $gmModel      = 'App\\Modules\\Group\\Models\\GroupMember';
        $coreRoleName = 'core-approval-member';
        $vcepTypeID   = config('groups.types.vcep.id');

        $base = DB::table('group_members as gm')
            ->join('groups as g', 'g.id', '=', 'gm.group_id')
            ->join('model_has_roles as mhr', function ($j) use ($gmModel) {
                $j->on('mhr.model_id', '=', 'gm.id')->where('mhr.model_type', '=', $gmModel);
            })
            ->join('roles as r', function ($j) use ($coreRoleName) {
                $j->on('r.id', '=', 'mhr.role_id')->where('r.name', '=', $coreRoleName);
            })
            ->whereNull('gm.end_date')
            ->where('g.group_type_id', $vcepTypeID)
            ->distinct()
            ->select('gm.person_id');

        $select = DB::query()
            ->fromSub($base, 'temp')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('attestations as a')
                ->whereColumn('a.person_id', 'temp.person_id');
            })
            ->selectRaw('UUID() as uuid, temp.person_id, NOW() as created_at, NOW() as updated_at');

        DB::table('attestations')->insertUsing(['uuid','person_id','created_at','updated_at'], $select);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attestations');
    }
};
