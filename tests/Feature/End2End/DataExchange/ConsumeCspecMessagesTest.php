<?php

namespace Tests\Feature\End2End\DataExchange;

use Carbon\Carbon;
use Tests\TestCase;
use App\DataExchange\DxMessage;
use Tests\Dummies\FakeMessageStream;
use App\DataExchange\Actions\DxConsume;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\RulesetStatusSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use App\DataExchange\Contracts\MessageStream;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\SpecificationStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * @group dx
 */
class ConsumeCspecMessagesTest extends TestCase
{
    use RefreshDatabase;

    private $vcep;
    private $consumer;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->vcep = ExpertPanel::factory()
                        ->vcep()
                        ->create([
                            'current_step' => 2,
                            'step_1_approval_date' => Carbon::now(),
                            'affiliation_id' => '59999',
                        ]);
        config(['dx.consume' => true]);

        $this->consumer = new DxConsume();
    }

    /**
     * @test
     */
    public function does_not_consume_if_dx_consume_config_is_false()
    {
        config(['dx.consume' => false]);
        app()->bind(MessageStream::class, fn () => new FakeMessageStream([
            new DxMessage(
                'cspec_general_events_test',
                time(),
                file_get_contents(test_path('files/cspec/cspec-classified-rules-approved.json')),
                1
            ),
        ]));

        Carbon::setTestNow('2022-08-18 00:00:00');
        Artisan::call('schedule:run');

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->vcep->id,
            'step_2_approval_date' =>  null,
            'current_step' => 2
        ]);
    }

    /**
     * @test
     * @return void
     */
    public function cspec_draft_rules_approved_message_consumed_and_handled()
    {
        $topic = 'cspec_general_events_test';
        $timestamp = time();
        app()->bind(MessageStream::class, fn () => new FakeMessageStream([
            new DxMessage(
                $topic,
                $timestamp,
                file_get_contents(test_path('files/cspec/cspec-classified-rules-approved.json')),
                1
            ),
        ]));

        Carbon::setTestNow('2022-08-18 00:00:00');
        // Artisan::call('schedule:run');
        $this->consumer->handle(config('dx.topics.incoming'));

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->vcep->id,
            'step_2_approval_date' =>  Carbon::createFromTimestamp($timestamp),
            'current_step' => 3
        ]);
    }

    /**
     * @test
     */
    public function cspec_pilot_rules_approved_message_consumed_and_handled()
    {
        $topic = 'cspec_general_events_test';
        $this->vcep->current_step = 3;
        $this->vcep->step_2_approval_date = Carbon::now();
        $this->vcep->save();

        $timestamp = time();
        app()->bind(MessageStream::class, fn () => new FakeMessageStream([
            new DxMessage(
                $topic,
                $timestamp,
                file_get_contents(test_path('files/cspec/cspec-pilot-rules-approved.json')),
                2
            ),
        ]));

        Carbon::setTestNow('2022-08-18 00:00:00');
        // Artisan::call('schedule:run');
        $this->consumer->handle(config('dx.topics.incoming'));


        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->vcep->id,
            'step_3_approval_date' => Carbon::createFromTimestamp($timestamp),
            'current_step' => 4
        ]);
    }
}
