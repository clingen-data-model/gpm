<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\GenesAdd;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\SeedsHgncGenesAndDiseases;

class PublishApplicationEventsTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();

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
            'topic' => config('dx.topics.outgoing.gpm-applications'),
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
            'topic' => config('dx.topics.outgoing.gpm-applications'),
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
            'topic' => config('dx.topics.outgoing.gpm-applications'),
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
            'topic' => config('dx.topics.outgoing.gpm-applications'),
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
            'topic' => config('dx.topics.outgoing.gpm-applications'),
            'message->event_type' => 'gene_added',
            'sent_at' => null,
        ]);
    }

    private function addPerson()
    {
        $person = Person::factory()->create();
        $this->json('POST', '/api/groups/'.$this->group->uuid.'/members/', [
            'person_id' => $person->id,
            'role_ids' => [],
            'is_contact' => 0
        ]);
    }

    private function addGene($genes = null)
    {
        $genes = $this->seedGenes([['hgnc_id' => 678, 'gene_symbol'=>'BCD'], ['hgnc_id' => 12345, 'gene_symbol' => 'ABC1']]);
        $action = app()->make(GenesAdd::class);
        $action->handle($this->expertPanel->group, $genes->pluck('gene_symbol'));
    }
    

    private function approveEpDef()
    {
        $this->json('POST', '/api/applications/'.$this->expertPanel->uuid.'/current-step/approve', [
            'date_approved' => Carbon::now(),
            'notify_contacts' => false,
        ])->assertStatus(200);
    }
}
