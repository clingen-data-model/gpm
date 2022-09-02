<?php

namespace Tests\Feature\Integration\DX\Actions;

use Tests\TestCase;
use Illuminate\Support\Collection;
use Database\Seeders\RulesetStatusSeeder;
use App\Modules\ExpertPanel\Models\Ruleset;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Database\Seeders\SpecificationStatusSeeder;
use App\Modules\ExpertPanel\Models\Specification;
use App\DataExchange\Models\IncomingStreamMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\DataExchange\Actions\CspecDataSyncProcessor;

class CspecDataSyncProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected IncomingStreamMessage $message;
    protected ExpertPanel $expertPanel;
    protected Collection $statuses;

    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();

        (new SpecificationStatusSeeder)->run();
        (new RulesetStatusSeeder)->run();

        $this->statuses = collect(config('specifications.statuses'))->keyBy('event');

        $this->expertPanel = ExpertPanel::factory()->vcep()->create(['affiliation_id' => 599999]);

        $this->message = new IncomingStreamMessage([
            'payload' => (object)[
                'cspecDoc' => [
                    'affiliationId' => 599999,
                    'name' => 'blah blah blah',
                    'cspecId' => 'BL004',
                    'ruleSets' => [
                        [
                            'ruleSetId' => '123456',
                        ]
                    ],
                    'status' => [
                        'event' => 'classified-rules-submitted'
                    ]
                ]
            ],
            'error_code' => 0
        ]);
    }

    /**
     * @test
     */
    public function creates_specification_and_rulset_if_none_exists()
    {
        app()->make(CspecDataSyncProcessor::class)
            ->handle($this->message);

        $this->assertDatabaseSynced();
    }

    /**
     * @test
     */
    public function updates_specification_and_ruleset_if_exists()
    {
        $specification = Specification::factory()->create([
            'cspec_id' => 'BL004',
            'name' => 'Early Bird Nini',
            'status_id' => 1,
            'expert_panel_id' => $this->expertPanel->id
        ]);
        $ruleset = Ruleset::factory()->create([
            'cspec_ruleset_id' => '123456',
            'specification_id' => 'BL004',
            'status_id' => 1
        ]);

        app()->make(CspecDataSyncProcessor::class)
            ->handle($this->message);

        $this->assertDatabaseSynced();

    }

    private function assertDatabaseSynced(): void
    {
        $this->assertDatabaseHas('specifications', [
            'cspec_id' => 'BL004',
            'name' => 'blah blah blah',
            'expert_panel_id' => $this->expertPanel->id,
            'status_id' => $this->statuses->get('classified-rules-submitted')['id']
        ]);

        $this->assertDatabaseHas('specification_rulesets', [
            'cspec_ruleset_id' => '123456',
            'specification_id' => 'BL004',
            'status_id' => $this->statuses->get('classified-rules-submitted')['id']
        ]);
   }



}
