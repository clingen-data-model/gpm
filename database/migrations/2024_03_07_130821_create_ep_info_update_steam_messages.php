<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Actions\DataFixes\CreateEpInfoUpdatedMessages;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $action = app()->make(CreateEpInfoUpdatedMessages::class);
        $action->handle();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $sql = 'delete from stream_messages where message->>"$.event_type" = "ep_info_updated"';
        DB::statement($sql);
    }
};
