<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('issuer');
            $table->string('sub');
            $table->string('idp')->nullable();
            $table->string('idp_name')->nullable();
            $table->string('email')->nullable();
            $table->string('orcid', 19)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['issuer', 'sub']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_identities');
    }
};
