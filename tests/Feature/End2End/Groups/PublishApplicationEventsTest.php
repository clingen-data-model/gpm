<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\GenesAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\ExpertPanelAffiliationIdUpdate;
use App\Modules\Group\Actions\ExpertPanelNameUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\SeedsHgncGenesAndDiseases;

/**
 * @group dx
 */
class PublishApplicationEventsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;

    protected $user, $expertPanel, $group;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();

        $this->user = $this->setupUser(null, ['ep-applications-manage']);
        $this->expertPanel = ExpertPanel::factory()->gcep()->create();
        $this->group = $this->expertPanel->group;

        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function it_publishes_definitionApproved_message_when_definition_approved()
    {
        $this->approveEpDef();

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'ep_definition_approved',
            'sent_at' => null,
        ]);
    }

    /**
     * @test
     */
    public function it_should_not_publish_memberAdded_when_def_not_yet_approved()
    {
        $this->addPerson();

        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'member_added',
        ]);
    }


    /**
     * @test
     */
    public function it_publishes_memberAdded_when_member_added_and_def_already_approved()
    {
        $this->approveEpDef();
        $this->addPerson();

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'member_added',
            'sent_at' => null,
        ]);
    }

    /**
     * @test
     */
    public function it_should_not_publish_GeneAdded_when_def_not_yet_approved()
    {
        $this->addGene();

        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'gene_added',
        ]);
    }


    /**
     * @test
     */
    public function it_publishes_GeneAdded_when_gene_added_and_def_already_approved()
    {
        $this->approveEpDef();
        $this->addGene();

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'genes_added',
            'sent_at' => null,
        ]);
    }

    /**
     * @test
     */
    public function it_publishes_ep_info_updated_when_affiliation_id_added(): void
    {
        $this->approveEpDef();
        (new ExpertPanelAffiliationIdUpdate())->handle($this->group, '12345');


        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'ep_info_updated',
            'message->data->group->affiliation_id' => '12345',
            // 'sent_at' => null,
        ]);
    }

    /**
     * @test
     */
    public function it_publishes_ep_info_updated_when_name_changed(): void
    {
        $this->approveEpDef();
        (new ExpertPanelNameUpdate())->handle($this->group, 'new name', 'new name');

        $this->group->refresh();

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'ep_info_updated',
            'message->data->group->name' => $this->group->name,
            // 'sent_at' => null,
        ]);
    }

    private function addPerson()
    {
        $person = Person::factory()->create();
        $this->json('POST', '/api/groups/' . $this->group->uuid . '/members/', [
            'person_id' => $person->id,
            'role_ids' => [],
            'is_contact' => 0
        ]);
    }

    private function addGene()
    {
        $genes = $this->seedGenes([['hgnc_id' => 678, 'gene_symbol' => 'BCD'], ['hgnc_id' => 12345, 'gene_symbol' => 'ABC1']]);
        $action = app()->make(GenesAdd::class);
        $action->handle($this->expertPanel->group, $genes->pluck('gene_symbol'));
    }


    private function approveEpDef()
    {
        $this->json('POST', '/api/applications/' . $this->expertPanel->group->uuid . '/current-step/approve', [
            'date_approved' => Carbon::now(),
            'notify_contacts' => false,
        ])->assertStatus(200);
    }
}
