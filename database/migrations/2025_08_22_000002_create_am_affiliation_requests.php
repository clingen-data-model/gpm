<?php

// database/migrations/2025_08_22_000002_create_am_affiliation_requests.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('am_affiliation_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('request_uuid');                 // expert_panels.uuid
            $t->unsignedBigInteger('expert_panel_id');
            $t->json('payload');
            $t->unsignedMediumInteger('http_status')->nullable();
            $t->json('response')->nullable();
            $t->string('status', 20)->default('pending'); // pending|success|failed
            $t->string('error')->nullable();
            $t->timestamps();
            $t->index(['expert_panel_id','status']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('am_affiliation_requests');
    }
};
