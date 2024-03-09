<?php

use App\Actions\DataFixes\CleanErrantStreamMessages;
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
        $this->cleanErrantMessages();
        $this->reIndexStreamMessages();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }

    private function cleanErrantMessages(): void
    {
        app()->make(CleanErrantStreamMessages::class)->handle();
    }

    private function reIndexStreamMessages(): void
    {
        app()->make(ReorderStreamMessages::class)->handle();
    }
    
    
};
