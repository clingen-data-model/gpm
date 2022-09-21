<?php

namespace Tests\Feature\Integration\DX\Actions;

use Tests\TestCase;
use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
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

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

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
                        'event' => 'classified-rules-submitted',
                        'current' => 'Classified Rules Submitted'
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
            'status' => 'New CSpec Doc Created',
            'expert_panel_id' => $this->expertPanel->id
        ]);
        $ruleset = Ruleset::factory()->create([
            'cspec_ruleset_id' => '123456',
            'specification_id' => 'BL004',
            'status' => 'New CSpec Doc Created'
        ]);

        app()->make(CspecDataSyncProcessor::class)
            ->handle($this->message);

        $this->assertDatabaseSynced();
    }

    /**
     * @test
     */
    public function status_change_is_logged()
    {
        $specification = Specification::factory()->create([
            'cspec_id' => 'BL004',
            'name' => 'Early Bird Nini',
            'status' => 'New CSpec Doc Created',
            'expert_panel_id' => $this->expertPanel->id
        ]);
        $ruleset = Ruleset::factory()->create([
            'cspec_ruleset_id' => '123456',
            'specification_id' => 'BL004',
            'status' => 'New CSpec Doc Created'
        ]);

        app()->make(CspecDataSyncProcessor::class)
            ->handle($this->message);

        $this->assertDatabaseHas('activity_log', [
            'activity_type' => 'specification-status-updated',
            'subject_id' => $this->expertPanel->group_id,
            'subject_type' => Group::class,
            'properties->specification_id' => 'BL004',
            'properties->status' => 'Classified Rules Submitted',
            'properties->step' => 2,
            'description' => 'Specification "blah blah blah" status updated to "Classified Rules Submitted"'
        ]);
    }


    private function assertDatabaseSynced(): void
    {
        $this->assertDatabaseHas('specifications', [
            'cspec_id' => 'BL004',
            'name' => 'blah blah blah',
            'expert_panel_id' => $this->expertPanel->id,
            'status' => 'Classified Rules Submitted'
        ]);

        $this->assertDatabaseHas('specification_rulesets', [
            'cspec_ruleset_id' => '123456',
            'specification_id' => 'BL004',
            'status' => 'Classified Rules Submitted'
        ]);
    }
}
