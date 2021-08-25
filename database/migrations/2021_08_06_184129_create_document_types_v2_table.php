<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDocumentTypesV2Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();

        if (!Schema::hasTable('document_types_v2')) {
            Schema::create('document_types_v2', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('long_name')->unique();
                $table->boolean('is_versioned')->default(0);
                $table->boolean('application_document')->default(0);
                $table->timestamps();
            });
        }

        foreach (config('documents.types') as $type) {
            DB::table('document_types_v2')
                ->updateOrInsert(['id' => $type['id']], [
                    'name' => $type['name'],
                    'long_name' => $type['long_name'],
                    'is_versioned' => $type['is_versioned'],
                    'application_document' => $type['application_document'],
                ]);
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_types');
    }
}
