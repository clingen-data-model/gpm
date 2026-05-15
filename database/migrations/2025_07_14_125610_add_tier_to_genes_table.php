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
            $table->string('moi', 20)->nullable()->after('disease_name');
            $table->unsignedInteger('tier')->nullable()->after('moi');
            $table->json('plan')->nullable()->after('tier');
            $table->uuid('gt_curation_uuid')->nullable()->after('plan');
            $table->index('gt_curation_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('genes', function (Blueprint $table) {      
            $table->dropColumn(['moi', 'tier', 'plan', 'gt_curation_uuid']);
        });
    }
};
