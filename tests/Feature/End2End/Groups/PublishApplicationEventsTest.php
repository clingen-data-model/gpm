<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Modules\Person\Models\Person;
use App\Modules\Group\Actions\GenesAdd;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Actions\ExpertPanelAffiliationIdUpdate;
use App\Modules\Group\Actions\ExpertPanelNameUpdate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\SeedsHgncGenesAndDiseases;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('dx')]
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

        $this->actingAs($this->user, 'clerk');
    }

    #[Test]
    public function it_publishes_gcep_approval_event_when_gcep_def_approved()
    {
        $this->approveEpDef();

        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'gcep_final_approval',
            'sent_at' => null,
        ]);
    }

    #[Test]
    public function it_should_not_publish_memberAdded_when_def_not_yet_approved()
    {
        $this->addPerson();

        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'member_added',
        ]);
    }


    #[Test]
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

    #[Test]
    public function it_should_not_publish_GeneAdded_when_def_not_yet_approved()
    {
        $this->addGene();

        $this->assertDatabaseMissing('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'gene_added',
        ]);
    }


    #[Test]
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

    #[Test]
    public function it_publishes_ep_info_updated_when_affiliation_id_added(): void
    {
        $this->approveEpDef();
        (new ExpertPanelAffiliationIdUpdate())->handle($this->group, '12345');


        $this->assertDatabaseHas('stream_messages', [
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message->event_type' => 'ep_info_updated',
            'message->data->affiliation_id' => '12345',
            // 'sent_at' => null,
        ]);
    }

    #[Test]
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
        $action->handle(
            $this->expertPanel->group,
            $genes->map(fn ($gene) => ['hgnc_id' => $gene->hgnc_id, 'gene_symbol' => $gene->gene_symbol])->all()
        );
    }


    private function approveEpDef()
    {
        $this->json('POST', '/api/applications/' . $this->expertPanel->group->uuid . '/current-step/approve', [
            'date_approved' => Carbon::now(),
            'notify_contacts' => false,
        ])->assertStatus(200);
    }
}
