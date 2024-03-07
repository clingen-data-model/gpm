<?php

use App\Actions\DataFixes\ReorderStreamMessages;
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
        $action = app()->make(ReorderStreamMessages::class);
        $action->handle();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
