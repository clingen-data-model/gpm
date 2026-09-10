<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foreign_component_members', function (Blueprint $table) {
            $table->id();
            $table->char('person_uuid', 36)->unique();
            $table->string('organization_name')->nullable();
            $table->string('city')->nullable();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->string('country_name')->nullable();
            $table->string('location_source', 20)->nullable();
            $table->dateTime('added_at')->nullable();
            $table->dateTime('last_unretired_at')->nullable();
            $table->dateTime('removed_at')->nullable();
            $table->unsignedInteger('active_membership_count')->default(0);
            $table->unsignedInteger('stream_membership_count')->nullable();
            $table->unsignedInteger('last_stream_message_id')->nullable();
            $table->json('stream_membership_state')->nullable();
            $table->boolean('is_in_scope')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foreign_component_members');
    }
};
