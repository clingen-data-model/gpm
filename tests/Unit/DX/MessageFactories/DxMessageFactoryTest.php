<?php

namespace Tests\Unit\DX\MessageFactories;

use Tests\TestCase;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Carbon;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GeneRemoved;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Models\GroupMember;
use App\Modules\Group\Events\MemberRemoved;
use App\Modules\Group\Events\MemberRetired;
use App\Modules\Group\Events\MemberUnretired;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\MemberRoleRemoved;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\Group\Events\MemberRoleAssigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Events\MemberPermissionRevoked;
use App\Modules\Group\Events\MemberPermissionGranted;
use App\DataExchange\MessageFactories\DxMessageFactory;

/**
 * @group dx
 */
class DxMessageFactoryTest extends TestCase
{
    use RefreshDatabase;

    private ExpertPanel $expertPanel;

    public function setup(): void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->setupPermission('application-edit');
        $this->expertPanel = ExpertPanel::factory()->create(['long_base_name' => 'TEST GROUP', 'affiliation_id' => 50666]);
        Gene::factory(2)->create(['expert_panel_id' => $this->expertPanel->id]);
        GroupMember::factory(2)->create(['group_id' => $this->expertPanel->group->id]);

        $this->expertPanel->load('genes', 'group', 'group.members', 'group.members.person');

        $this->factory = new DxMessageFactory();
    }

    /**
     * @test
     */
    public function it_creates_a_definition_approved_message()
    {
        $event = new StepApproved($this->expertPanel, 1, Carbon::now());
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('ep_definition_approved', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertMembersInMessage($this->expertPanel->group->members->all(), $message);
        $this->assertArrayHasKey('scope', $message['data']);
        $this->assertGenesInMessage($this->expertPanel->genes->all(), $message['data']['scope']);
        $this->assertEquals(config('dx.schema_versions.gpm-general-events'), $message['schema_version']);
    }

    /**
     * @test
     */
    public function it_creates_a_member_added_message()
    {
        $event = new MemberAdded($this->expertPanel->group->members->first());

        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('member_added', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    /**
     * @test
     */
    public function it_creates_member_role_assigned_message()
    {
        $roles = Role::where('name', 'coordinator')->get();
        $event = new MemberRoleAssigned($this->expertPanel->group->members->first(), $roles);
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('member_role_assigned', $message['event_type']);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    /**
     * @test
     */
    public function it_creates_member_role_removed_message()
    {
        $roles = Role::where('name', 'coordinator')->get();
        $event = new MemberRoleRemoved($this->expertPanel->group->members->first(), $roles->first());
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('member_role_removed', $message['event_type']);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    /**
     * @test
     */
    public function it_creates_member_permission_granted_message()
    {
        $permissions = Permission::where('name', 'application-edit')->get();
        $event = new MemberPermissionGranted($this->expertPanel->group->members->first(), $permissions);
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('member_permission_granted', $message['event_type']);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    /**
     * @test
     */
    public function it_creates_member_permission_revoked_message()
    {
        $permissions = Permission::where('name', 'application-edit')->get();
        $event = new MemberPermissionRevoked($this->expertPanel->group->members->first(), $permissions->first());
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('member_permission_revoked', $message['event_type']);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }



    /**
     * @test
     */
    public function it_creates_a_member_removed_event()
    {
        $groupMember = $this->expertPanel->group->members->first();
        $removeTime = Carbon::now();
        $groupMember->update(['end_date' => $removeTime->toISOString()]);
        $groupMember->delete();

        $event = new MemberRemoved($groupMember);

        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('member_removed', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    /**
     * @test
     */
    public function it_creates_a_member_retired_event()
    {
        $groupMember = $this->expertPanel->group->members->first();
        $retireTime = Carbon::now();
        $groupMember->update(['end_date' => $retireTime->toISOString()]);
        $event = new MemberRetired($groupMember);

        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('member_retired', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertMembersInMessage([$groupMember], $message);
    }

    /**
     * @test
     */
    public function it_creates_a_member_unretired_event()
    {
        $event = new MemberUnretired($this->expertPanel->group->members->first());

        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('member_unretired', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    /**
     * @test
     */
    public function it_creates_a_gene_added_message_when_new_gene_added_to_scope()
    {
        $event = new GenesAdded($this->expertPanel->group, collect([$this->expertPanel->genes->first()]));
        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('genes_added', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertGenesInMessage([$this->expertPanel->genes->first()], $message);
    }

    /**
     * @test
     */
    public function it_creates_a_gene_removed_message_when_removed_gene_approved()
    {
        $event = new GeneRemoved($this->expertPanel->group, $this->expertPanel->genes->first());
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('gene_removed', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertGenesInMessage([$this->expertPanel->genes->first()], $message);
    }

    private function assertGenesInMessage($genes, $message)
    {
        $comparisonArray = isset($message['data']) ? $message['data'] : $message;

        $this->assertArrayHasKey('genes', $comparisonArray);

        $genesMsg = array_map(function ($gene) {
            return [
                'hgnc_id' => $gene->hgnc_id,
                'gene_symbol' => $gene->gene_symbol
            ];
        }, $genes);

        $this->assertEquals($genesMsg, $comparisonArray['genes']);
    }


    private function assertMembersInMessage($members, $message)
    {
        $membersMsg = array_map(function ($member) {
            return [
                'uuid' => $member->person->uuid,
                'first_name' => $member->person->first_name,
                'last_name' => $member->person->last_name,
                'roles' => $member->roles->pluck('name')->toArray(),
                'institution' => $member->person->institution->name ?? null,
                'credentials' => $member->person->credentials->pluck('name')->toArray(),
            ];
        }, $members);
        $this->assertArrayHasKey('members', $message['data']);
        $this->assertEquals($membersMsg, $message['data']['members']);
    }


    private function assertExpertPanelInMessage($message)
    {
        $this->assertEquals([
            'uuid' => $this->expertPanel->group->uuid,
            'name' => $this->expertPanel->group->name,
            'description' => $this->expertPanel->group->description,
            'status' => $this->expertPanel->group->groupStatus->name,
            'type' => $this->expertPanel->group->fullType->name,
            'caption' => $this->expertPanel->group->caption,
            'coi' => url('/coi-group/'.$this->expertPanel->group->uuid),
            'expert_panel' => [
                'uuid' => $this->expertPanel->uuid,
                'affiliation_id' => (string) $this->expertPanel->affiliation_id,
                'name' => $this->expertPanel->long_base_name,
                'short_name' => $this->expertPanel->short_base_name,
                'scope_description' => $this->expertPanel->scope_description,
                'membership_description' => $this->expertPanel->membership_description,
                'type' => $this->expertPanel->type->name,
                'date_completed' => $this->expertPanel->date_completed,
                'current_step' => $this->expertPanel->current_step,
                'gcep_define_group' => $this->expertPanel->step_1_received_date,
                'gcep_approval' => $this->expertPanel->step_4_approval_date,
                'inactive_date' => null,
            ],
            'status_date' => $this->expertPanel->group->groupStatus->updated_at->toISO8601String(),
            'icon_url' => $this->expertPanel->group->iconUrl,
        ], $message['data']['group']);
    }
}
