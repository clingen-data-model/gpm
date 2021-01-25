<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->string('filename');
            $table->string('storage_path')->unique();
            $table->unsignedBigInteger('application_id');
            $table->integer('step_number')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('document_category_id');
            $table->integer('version')->default(1);
            $table->date('date_received')->useCurrent();
            $table->date('date_reviewed')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('application_id')->references('id')->on('applications');
            $table->foreign('document_category_id')->references('id')->on('document_categories');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documents');
    }
}
