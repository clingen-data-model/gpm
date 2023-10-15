<?php

use App\Modules\ExpertPanel\Models\Coi;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('cois', function (Blueprint $table) {
            $table->string('version', 5)->after('data');
        });

        Coi::all()->each->update(['version' => '1.0.0']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('cois', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
