<?php

namespace Tests\Unit\Actions\DataFixes;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Facades\Schema;
use App\DataExchange\Models\StreamMessage;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

class ReorderStreamMessagesTest extends TestCase
{
    use FastRefreshDatabase;

    private $tenDaysAgo;

    public function setUp():void
    {
        parent::setup();
        Carbon::setTestNow('2024-03-01 12:00:00');
        $this->tenDaysAgo = now()->subDays(10);
        StreamMessage::factory(9)->create(['sent_at' => now(), 'created_at' => now()]);
        streamMessage::factory()->create(['created_at' => $this->tenDaysAgo]);
    }

    /**
     * @test
     */
    public function it_reorders_the_stream_messages():void
    {
        $this->artisan('data-fix:reorder-stream-messages');

        $this->assertEquals(10, StreamMessage::count());

        $first = StreamMessage::orderBy('id')->first();
        $this->assertEquals($this->tenDaysAgo, $first->created_at);
        $this->assertEquals(1, $first->id);
    }

    /**
     * @test
     */
    public function it_cleans_up_after_itself():void
    {
        $this->artisan('data-fix:reorder-stream-messages');

        $this->assertFalse(Schema::hasTable('sm_temp'));
        $this->assertFalse(Schema::hasTable('sm_backup'));
    }
}
