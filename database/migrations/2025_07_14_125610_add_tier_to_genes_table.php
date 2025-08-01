<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('genes', function (Blueprint $table) {
            $table->unsignedTinyInteger('tier')->nullable()->after('gene_symbol');
            $table->string('moi', 20)->nullable()->after('mondo_id');            
            $table->json('plan')->nullable()->after('moi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genes', function (Blueprint $table) {
            $table->dropColumn('tier');
        });
    }
};
