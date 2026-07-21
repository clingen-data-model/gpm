<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consortium_identity_import_rows', function (Blueprint $table) {
            $table->id();

            $table->uuid('import_batch_uuid')->index();
            $table->string('source_system', 50)->index(); // GPM / GT / CCDB
            $table->string('local_user_id', 255);

            $table->string('email')->nullable();
            $table->string('email_normalized')->nullable()->index();

            $table->string('full_name')->nullable();
            $table->string('full_name_normalized')->nullable()->index();

            $table->char('gpm_uuid', 36)->nullable()->index();
            $table->text('password_digest')->nullable();
            $table->string('password_hasher')->nullable();

            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            $table->boolean('has_email')->default(false)->index();
            $table->boolean('has_gpm_uuid')->default(false)->index();

            $table->json('flags')->nullable();
            $table->json('raw_payload')->nullable();

            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['import_batch_uuid', 'source_system', 'local_user_id'],
                'consortium_identity_import_rows_batch_source_local_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consortium_identity_import_rows');
    }
};