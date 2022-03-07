<?php

namespace Tests\Unit\DX\MessageFactories;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Role;
use App\Models\Activity;
use App\Models\Permission;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Actions\GenesAdd;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Events\GeneRemoved;
use App\Modules\Group\Events\MemberAdded;
use App\Modules\Group\Models\GroupMember;
use Database\Factories\GroupMemberFactory;
use App\Modules\Group\Events\MemberRemoved;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\GeneAddedApproved;
use App\Modules\Group\Events\MemberRoleRemoved;
use App\Modules\ExpertPanel\Events\StepApproved;
use App\Modules\Group\Events\MemberRoleAssigned;
use App\Modules\Group\Events\GeneRemovedApproved;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Events\MemberPermissionRevoked;
use App\Modules\Group\Events\MemberPermissionsGranted;
use App\DataExchange\MessageFactories\ApplicationEventV1MessageFactory;

class ApplicationEventV1MessageFactoryFromActivityTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->expertPanel = ExpertPanel::factory()->create(['long_base_name'=>'TEST GROUP', 'affiliation_id' => 50666]);
        Gene::factory(2)->create(['expert_panel_id' => $this->expertPanel->id]);
        GroupMember::factory(2)->create(['group_id' => $this->expertPanel->group->id]);
        
        $this->expertPanel->load('genes', 'group', 'group.members', 'group.members.person');
        
        $this->factory = new ApplicationEventV1MessageFactory();
    }

    /**
     * @test
     */
    public function it_creates_a_definition_approved_message()
    {
        $activity = $this->makeActivity('step-approved', ['step' => 1]);
        $message = $this->factory->makeFromLogEntry($activity);

        $this->assertEquals('ep_definition_approved', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertMembersInMessage($this->expertPanel->group->members->all(), $message);
        $this->assertArrayHasKey('scope', $message['data']);
        $this->assertArrayHasKey('date', $message);
        $this->assertGenesInMessage($this->expertPanel->genes->all(), $message['data']['scope']);
    }

    /**
     * @test
     */
    public function it_creates_a_member_added_message()
    {
        $activity = $this->makeActivity('member-added', ['person' => $this->expertPanel->group->members->first()->toArray()]);
        
        $message = $this->factory->makeFromLogEntry($activity);
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
        $activity = $this->makeActivity(
            'member-role-assigned',
            [
                'member' => [
                    'id' => $this->expertPanel->group->members->first()->id,
                    'name' => $this->expertPanel->group->members->first()->person->name,
                    'email' => $this->expertPanel->group->members->first()->person->email,
                ]
            ]
        );
        $message = $this->factory->makeFromLogEntry($activity);

        $this->assertEquals('member_role_assigned', $message['event_type']);
        $this->assertArrayHasKey('date', $message);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }
    
    /**
     * @test
     */
    public function it_creates_member_role_removed_message()
    {
        $roles = Role::where('name', 'coordinator')->get();
        $activity = $this->makeActivity(
            'member-role-removed',
            [
                'member' => [
                    'id' => $this->expertPanel->group->members->first()->id,
                    'name' => $this->expertPanel->group->members->first()->person->name,
                    'email' => $this->expertPanel->group->members->first()->person->email,
                ]
            ]
        );
        $message = $this->factory->makeFromLogEntry($activity);
        // $event = new MemberRoleRemoved($this->expertPanel->group->members->first(), $roles->first());
        // $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('member_role_removed', $message['event_type']);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }
    
    /**
     * @test
     */
    public function it_creates_member_permission_granted_message()
    {
        $permissions = Permission::where('name', 'application-edit')->get();
        $activity = $this->makeActivity(
            'member-permissions-granted',
            [
                'member' => [
                    'id' => $this->expertPanel->group->members->first()->id,
                    'name' => $this->expertPanel->group->members->first()->person->name,
                    'email' => $this->expertPanel->group->members->first()->person->email,
                ]
            ]
        );
        $message = $this->factory->makeFromLogEntry($activity);
        // $event = new MemberPermissionsGranted($this->expertPanel->group->members->first(), $permissions);
        // $message = $this->factory->makeFromEvent($event);

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
        $event = new MemberRemoved($this->expertPanel->group->members->first());
        
        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('member_removed', $message['event_type']);
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
        $this->assertEquals('gene_added', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertGenesInMessage([$this->expertPanel->genes->first()], $message);
    }
    
    /**
     * @test
     */
    public function it_creates_a_gene_removeded_message_when_removed_gene_approved()
    {
        $event = new GeneRemoved($this->expertPanel->group, $this->expertPanel->genes->first());
        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('gene_removed', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertGenesInMessage([$this->expertPanel->genes->first()], $message);
    }

    private function makeActivity($type, $properties): Activity
    {
        $activity = new Activity([
            'activity_type' => $type,
            'properties' => $properties,
            'subject_type' => Group::class,
            'subject_id' => $this->expertPanel->group_id
        ]);
        $activity->created_at = Carbon::now();

        return $activity;
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
                'id' => $member->person->uuid,
                'first_name' => $member->person->first_name,
                'last_name' => $member->person->last_name,
                'email' => $member->person->email,
                'group_roles' => $member->roles->pluck('name')->toArray(),
                'additional_permissions' => $member->permissions->pluck('name')->toArray(),
            ];
        }, $members);
        $this->assertArrayHasKey('members', $message['data']);
        $this->assertEquals($membersMsg, $message['data']['members']);
    }
    

    private function assertExpertPanelInMessage($message)
    {
        $this->assertEquals([
            'id' => $this->expertPanel->group->uuid,
            'name' => $this->expertPanel->group->displayName,
            'type' => $this->expertPanel->group->fullType->name,
            'affiliation_id' => $this->expertPanel->affiliation_id
         ], $message['data']['expert_panel']);
    }
}
