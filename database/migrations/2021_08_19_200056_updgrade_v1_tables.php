<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdgradeV1Tables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('next_actions', 'next_actions_v1');
        Schema::rename('cois', 'cois_v1');
        Schema::rename('document_types', 'document_types_v1');
        Schema::rename('documents', 'documents_v1');

        Schema::rename('next_actions_v2', 'next_actions');
        Schema::rename('cois_v2', 'cois');
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
        Schema::rename('next_actions', 'next_actions_v2');
        Schema::rename('cois', 'cois_v2');
        Schema::rename('document_types', 'document_types_v2');
        Schema::rename('documents', 'documents_v2');

        Schema::rename('next_actions_v1', 'next_actions');
        Schema::rename('cois_v1', 'cois');
        Schema::rename('document_types_v1', 'document_types');
        Schema::rename('documents_v1', 'documents');
    }
}
