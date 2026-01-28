<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('affiliation_microservice_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('request_uuid');
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
        Schema::dropIfExists('affiliation_microservice_requests');
    }
};
