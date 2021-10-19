<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdgradeV1Tables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('cois_temp')) {
            Schema::create('cois_temp', function ($table) {
                $table->id();
                $table->foreignId('expert_panel_id')
                    ->constrained()
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->json('data');
                $table->timestamps();
            });
        }

        $coiData = DB::table('cois')
                        ->get()
                        ->map(function ($coi) {
                            $coi->expert_panel_id = $coi->application_id;
                            unset($coi->application_id);
                            return (array)$coi;
                        })
                        ->toArray();
        foreach ($coiData as $row) {
            DB::table('cois_temp')->insert($row);
        }
        // DB::table('cois_temp')->insert($coiData);
        Schema::dropIfExists('cois');
        Schema::rename('cois_temp', 'cois');

        Schema::rename('next_actions', 'next_actions_v1');
        Schema::rename('document_types', 'document_types_v1');
        Schema::rename('documents', 'documents_v1');

        Schema::rename('next_actions_v2', 'next_actions');
        Schema::rename('document_types_v2', 'document_types');
        Schema::rename('documents_v2', 'documents');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('cois_temp', function ($table) {
            $table->id();
            $table->foreignId('application_id')
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->json('data');
            $table->timestamps();
        });

        DB::table('cois_temp')->insert(DB::table('cois')->get()->toArray());
        Schema::dropIfExists('cois');
        Schema::rename('cois_temp', 'cois');

        Schema::rename('next_actions', 'next_actions_v2');
        Schema::rename('document_types', 'document_types_v2');
        Schema::rename('documents', 'documents_v2');

        Schema::rename('next_actions_v1', 'next_actions');
        Schema::rename('document_types_v1', 'document_types');
        Schema::rename('documents_v1', 'documents');
    }
}
