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
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('dx')]
class DxMessageFactoryTest extends TestCase
{
    use RefreshDatabase;

    private ExpertPanel $expertPanel;

    #[Test]
    public function direct_sow_changes_are_suppressed_only_for_active_revisions()
    {
        $group = $this->expertPanel->group;
        $this->expertPanel->step_1_approval_date = now();
        $group->setRelation('expertPanel', $this->expertPanel);
        $member = $group->members->first();
        $member->setRelation('group', $group);
        $events = [
            new GenesAdded($group, $this->expertPanel->genes),
            new GeneRemoved($group, $this->expertPanel->genes->first()),
            new \App\Modules\Group\Events\GroupNameUpdated($group, 'Draft', 'Official'),
            new MemberAdded($member), new MemberRemoved($member),
            new MemberRetired($member), new MemberUnretired($member),
            new \App\Modules\Group\Events\MemberUpdated($member, []),
            new MemberRoleAssigned($member, collect()),
            new MemberRoleRemoved($member, new Role(['name' => 'chair', 'display_name' => 'Chair'])),
        ];
        foreach ($events as $event) {
            $this->assertTrue($event->shouldPublish(), get_class($event));
        }
        $revision = \App\Modules\Group\Models\ScopeOfWorkVersion::create([
            'group_id' => $group->id, 'major_version' => 1, 'minor_version' => 0, 'status' => 'draft',
        ]);
        $publisher = app(\App\Actions\EventPublish::class);
        $before = \App\DataExchange\Models\StreamMessage::count();
        foreach (['draft', 'submitted', 'revisions_requested'] as $status) {
            $revision->update(['status' => $status]);
            foreach ($events as $event) {
                $this->assertFalse($event->shouldPublish(), get_class($event).' '.$status);
                $this->assertNull($publisher->handle($event));
            }
        }
        $this->assertSame($before, \App\DataExchange\Models\StreamMessage::count());
        foreach (['approved', 'discarded'] as $status) {
            $revision->update(['status' => $status]);
            foreach ($events as $event) {
                $this->assertTrue($event->shouldPublish(), get_class($event).' '.$status);
            }
        }
        $this->expertPanel->step_1_approval_date = null;
        foreach ($events as $event) {
            $this->assertFalse($event->shouldPublish(), 'Pre-definition block: '.get_class($event));
        }
    }

    #[Test]
    public function unrelated_events_keep_publishing_through_a_sow_revision_and_approval_publishes_final_state()
    {
        $group = $this->expertPanel->group;
        $this->expertPanel->step_1_approval_date = now();
        $group->setRelation('expertPanel', $this->expertPanel);
        $snapshot = \App\Modules\Group\Actions\ScopeOfWork\SnapshotBuild::run($group);
        $snapshot['scope_of_work']['panel_name'] = 'Official baseline';
        $baseline = \App\Modules\Group\Models\ScopeOfWorkVersion::create([
            'group_id' => $group->id, 'major_version' => 1, 'minor_version' => 0, 'status' => 'approved',
        ]);
        $baseline->snapshots()->create(['snapshot_schema_version' => '1.0.0', 'snapshot' => $snapshot]);
        $publisher = app(\App\Actions\EventPublish::class);

        // Approved history alone must not override live data.
        $group->name = 'Current live name';
        $live = $publisher->handle(new \App\Modules\Group\Events\GroupNameUpdated($group, $group->name, 'Earlier name'));
        $this->assertNotNull($live);
        $this->assertSame('Current live name', $live->message['data']['group']['name']);

        $revision = \App\Modules\Group\Models\ScopeOfWorkVersion::create([
            'group_id' => $group->id, 'major_version' => 2, 'minor_version' => 0, 'status' => 'draft',
        ]);
        foreach (['draft', 'submitted', 'revisions_requested'] as $status) {
            $revision->update(['status' => $status]);
            $event = new \App\Modules\Group\Events\GroupDescriptionUpdated($group, 'Live description', 'Earlier description');
            $this->assertTrue($event->shouldPublish(), $status);
            $published = $publisher->handle($event);
            $this->assertNotNull($published, $status);
            $this->assertSame('Official baseline', $published->message['data']['group']['name']);
            $this->assertSame('Live description', $published->message['data']['new_description']);
        }

        $submission = new \App\Modules\Group\Models\Submission(['group_id' => $group->id]);
        $submission->setRelation('group', $group);
        $requested = new \App\Modules\Group\Events\ScopeOfWorkReviewCompleted($submission, $revision, 'revisions_requested');
        $before = \App\DataExchange\Models\StreamMessage::count();
        $this->assertNull($publisher->handle($requested));
        $this->assertSame($before, \App\DataExchange\Models\StreamMessage::count());
        $this->assertArrayNotHasKey('group', $requested->getPublishableMessage());

        $group->name = 'Final approved name';
        $revision->update(['status' => 'approved']);
        $finalSnapshot = \App\Modules\Group\Actions\ScopeOfWork\SnapshotBuild::run($group);
        $revision->snapshots()->create(['snapshot_schema_version' => '1.0.0', 'snapshot' => $finalSnapshot]);
        $approved = new \App\Modules\Group\Events\ScopeOfWorkReviewCompleted($submission, $revision, 'approved');
        $published = $publisher->handle($approved);
        $this->assertNotNull($published);
        $this->assertSame('scope_of_work_review_completed', $published->message['event_type']);
        $this->assertSame('Final approved name', $published->message['data']['group']['name']);
        $this->assertCount(2, $published->message['data']['group']['members']);
        $this->assertCount(2, $published->message['data']['group']['expert_panel']['all_genes']);
    }

    #[Test]
    public function only_approved_sow_reviews_publish_the_full_group_state()
    {
        $group = $this->expertPanel->group;
        $submission = new \App\Modules\Group\Models\Submission(['group_id' => $group->id]);
        $submission->setRelation('group', $group);
        $revision = new \App\Modules\Group\Models\ScopeOfWorkVersion(['status' => 'approved']);
        $event = new \App\Modules\Group\Events\ScopeOfWorkReviewCompleted($submission, $revision, 'approved');

        $this->expertPanel->step_1_approval_date = null;
        $group->setRelation('expertPanel', $this->expertPanel);
        $this->assertFalse($event->shouldPublish());
        $this->expertPanel->step_1_approval_date = now();
        $this->assertTrue($event->shouldPublish());

        $message = $this->factory->makeFromEvent($event);
        $this->assertSame('scope_of_work_review_completed', $message['event_type']);
        $this->assertSame('approved', $message['data']['outcome']);
        $this->assertEquals($event->mapGroupForMessage(true, true), $message['data']['group']);
        $this->assertCount(2, $message['data']['group']['members']);
        $this->assertCount(2, $message['data']['group']['expert_panel']['all_genes']);

        $requested = new \App\Modules\Group\Events\ScopeOfWorkReviewCompleted($submission, $revision, 'revisions_requested');
        $this->assertFalse($requested->shouldPublish());
        $this->assertArrayNotHasKey('group', $requested->getPublishableMessage());
    }

    #[Test]
    public function uncaptured_snapshot_fields_stay_live_but_explicit_empty_values_are_projected()
    {
        $group = $this->expertPanel->group;
        $version = \App\Modules\Group\Models\ScopeOfWorkVersion::create([
            'group_id' => $group->id, 'major_version' => 1, 'minor_version' => 0, 'status' => 'approved',
        ]);
        $snapshot = $version->snapshots()->create([
            'snapshot_schema_version' => '1.0.0',
            'snapshot' => ['group' => [], 'expert_panel' => [], 'scope_of_work' => []],
        ]);
        \App\Modules\Group\Models\ScopeOfWorkVersion::create([
            'group_id' => $group->id, 'major_version' => 2, 'minor_version' => 0, 'status' => 'draft',
        ]);
        $events = [
            new \App\Modules\Group\Events\GroupCheckpointEvent($group),
            new \App\Modules\Group\Events\GroupNameUpdated($group, 'Live new', 'Live old'),
            new GenesAdded($group, $this->expertPanel->genes),
            new MemberRoleAssigned($group->members->first(), collect()),
            new StepApproved($this->expertPanel, 1, Carbon::now()),
        ];
        foreach ($events as $event) {
            $this->assertEquals($event->getPublishableMessage(), $this->factory->makeFromEvent($event)['data']);
        }
        $snapshot->update(['snapshot' => [
            'group' => ['name' => 'Snapshot group name'],
            'expert_panel' => ['short_base_name' => null],
            'scope_of_work' => ['scope_description' => null, 'membership_description' => null, 'scope_genes' => [], 'members' => []],
        ]]);
        $data = $this->factory->makeFromEvent($events[0])['data'];
        $this->assertSame('Snapshot group name', $data['name']);
        $this->assertNull($data['expert_panel']['short_name']);
        $this->assertNull($data['expert_panel']['scope_description']);
        $this->assertNull($data['expert_panel']['membership_description']);
        $this->assertSame([], $data['expert_panel']['all_genes']);
        $this->assertSame([], $data['members']);
        $this->assertSame($group->description, $data['description']);
        $this->assertSame($group->excerpt, $data['excerpt']);
    }

    #[Test]
    public function sow_payloads_use_the_latest_approved_snapshot_only_while_a_revision_is_active()
    {
        $group = $this->expertPanel->group;
        $snapshot = \App\Modules\Group\Actions\ScopeOfWork\SnapshotBuild::run($group);
        $snapshot['scope_of_work']['panel_name'] = 'Approved name';
        $snapshot['expert_panel']['long_base_name'] = 'Approved panel';
        $snapshot['expert_panel']['scope_description'] = 'Approved scope';
        $snapshot['scope_of_work']['scope_description'] = 'Approved scope';
        $snapshot['scope_of_work']['scope_genes'][0]['gene_symbol'] = 'APPROVED';
        $snapshot['scope_of_work']['members'][0]['roles'] = [
            ['id' => 1, 'name' => 'chair', 'display_name' => 'Chair'],
        ];
        $version = \App\Modules\Group\Models\ScopeOfWorkVersion::create([
            'group_id' => $group->id, 'major_version' => 1, 'minor_version' => 0, 'status' => 'approved',
        ]);
        $version->snapshots()->create(['snapshot_schema_version' => '1.0.0', 'snapshot' => $snapshot]);
        // Insert an older version later: selection must use version order, not row order.
        $older = \App\Modules\Group\Models\ScopeOfWorkVersion::create([
            'group_id' => $group->id, 'major_version' => 0, 'minor_version' => 9, 'status' => 'approved',
        ]);
        $olderSnapshot = $snapshot;
        $olderSnapshot['scope_of_work']['panel_name'] = 'Older approved name';
        $older->snapshots()->create(['snapshot_schema_version' => '1.0.0', 'snapshot' => $olderSnapshot]);
        $revision = \App\Modules\Group\Models\ScopeOfWorkVersion::create([
            'group_id' => $group->id, 'major_version' => 2, 'minor_version' => 0, 'status' => 'draft',
        ]);
        $event = new \App\Modules\Group\Events\GroupCheckpointEvent($group);
        foreach (['draft', 'submitted', 'revisions_requested'] as $status) {
            $revision->update(['status' => $status]);
            $data = $this->factory->makeFromEvent($event)['data'];
            $this->assertSame('Approved name', $data['name']);
            $this->assertSame('Approved panel', $data['expert_panel']['name']);
            $this->assertSame('Approved scope', $data['expert_panel']['scope_description']);
            $this->assertSame('APPROVED', $data['expert_panel']['all_genes'][0]['gene_symbol']);
            $this->assertSame(['Chair'], $data['members'][0]['roles']);
            $this->assertArrayHasKey('email', $data['members'][0]);
            $this->assertSame($group->description, $data['description']);
            $nameEvent = new \App\Modules\Group\Events\GroupNameUpdated($group, 'Draft name', 'Previous draft');
            $this->assertSame('Approved name', $this->factory->makeFromEvent($nameEvent)['data']['new_name']);
            $this->assertSame('Draft name', $nameEvent->getProperties()['new_name']);
            $this->assertFalse($nameEvent->shouldPublish());
        }
        $gene = $this->expertPanel->genes->firstWhere('id', $snapshot['scope_of_work']['scope_genes'][0]['id']);
        $gene->gene_symbol = 'DRAFT';
        $geneEvent = new GenesAdded($group, collect([$gene]));
        $geneData = $this->factory->makeFromEvent($geneEvent)['data']['genes'];
        $this->assertCount(1, $geneData);
        $this->assertSame('APPROVED', $geneData[0]['gene_symbol']);
        $newGene = new Gene(['gene_symbol' => 'NEW_DRAFT', 'hgnc_id' => 'HGNC:999999']);
        $newGene->id = 999999;
        $this->assertSame([], $this->factory->makeFromEvent(new GenesAdded($group, collect([$newGene])))['data']['genes']);
        $member = GroupMember::find($snapshot['scope_of_work']['members'][0]['id']);
        $memberEvent = new MemberRoleAssigned($member, collect());
        $memberData = $this->factory->makeFromEvent($memberEvent)['data'];
        $this->assertCount(1, $memberData['members']);
        $this->assertSame(['Chair'], $memberData['members'][0]['roles']);
        $this->assertSame(['Chair'], $memberData['roles']);
        $this->expertPanel->step_1_approval_date = now();
        $group->setRelation('expertPanel', $this->expertPanel);
        $this->assertFalse($geneEvent->shouldPublish());
        $this->assertFalse($nameEvent->shouldPublish());
        foreach (['approved', 'discarded'] as $status) {
            $revision->update(['status' => $status]);
            $this->assertEquals($event->getPublishableMessage(), $this->factory->makeFromEvent($event)['data']);
        }
        $revision->update(['status' => 'draft']);
        $version->update(['status' => 'discarded']);
        $older->update(['status' => 'discarded']);
        $this->assertEquals($event->getPublishableMessage(), $this->factory->makeFromEvent($event)['data']);
    }

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

    #[Test]
    public function it_creates_a_definition_approved_message()
    {
        $event = new StepApproved($this->expertPanel, 1, Carbon::now());
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('gcep_final_approval', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertMembersInMessage($this->expertPanel->group->members->all(), $message);
        $this->assertArrayHasKey('scope', $message['data']);
        $this->assertGenesInMessage($this->expertPanel->genes->all(), $message['data']['scope']);
        $this->assertEquals(config('dx.schema_versions.gpm-general-events'), $message['schema_version']);
    }

    #[Test]
    public function it_creates_a_member_added_message()
    {
        $event = new MemberAdded($this->expertPanel->group->members->first());

        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('member_added', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    #[Test]
    public function it_creates_member_role_assigned_message()
    {
        $roles = Role::where('name', 'coordinator')->get();
        $event = new MemberRoleAssigned($this->expertPanel->group->members->first(), $roles);
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('member_role_assigned', $message['event_type']);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    #[Test]
    public function it_creates_member_role_removed_message()
    {
        $roles = Role::where('name', 'coordinator')->get();
        $event = new MemberRoleRemoved($this->expertPanel->group->members->first(), $roles->first());
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('member_role_removed', $message['event_type']);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    #[Test]
    public function it_creates_member_permission_granted_message()
    {
        $permissions = Permission::where('name', 'application-edit')->get();
        $event = new MemberPermissionGranted($this->expertPanel->group->members->first(), $permissions);
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('member_permission_granted', $message['event_type']);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    #[Test]
    public function it_creates_member_permission_revoked_message()
    {
        $permissions = Permission::where('name', 'application-edit')->get();
        $event = new MemberPermissionRevoked($this->expertPanel->group->members->first(), $permissions->first());
        $message = $this->factory->makeFromEvent($event);

        $this->assertEquals('member_permission_revoked', $message['event_type']);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }



    #[Test]
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

    #[Test]
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

    #[Test]
    public function it_creates_a_member_unretired_event()
    {
        $event = new MemberUnretired($this->expertPanel->group->members->first());

        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('member_unretired', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertMembersInMessage([$this->expertPanel->group->members->first()], $message);
    }

    #[Test]
    public function it_creates_a_gene_added_message_when_new_gene_added_to_scope()
    {
        $event = new GenesAdded($this->expertPanel->group, collect([$this->expertPanel->genes->first()]));
        $message = $this->factory->makeFromEvent($event);
        $this->assertEquals('genes_added', $message['event_type']);
        $this->assertExpertPanelInMessage($message);
        $this->assertGenesInMessage([$this->expertPanel->genes->first()], $message);
    }

    #[Test]
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
        $this->assertArrayHasKey('members', $message['data']);

        $actualMembers = $message['data']['members'];
        $this->assertCount(count($members), $actualMembers);

        foreach ($members as $index => $member) {
            $actual = $actualMembers[$index];

            $this->assertEquals($member->person->uuid, $actual['uuid']);
            $this->assertEquals($member->person->first_name, $actual['first_name']);
            $this->assertEquals($member->person->last_name, $actual['last_name']);
            $this->assertEquals($member->roles->pluck('display_name')->toArray(), $actual['roles']);
            $this->assertEquals($member->permissions->pluck('name')->toArray(), $actual['additional_permissions']);
            $this->assertEquals($member->person->institution->name ?? null, $actual['institution']);
            $this->assertEquals($member->person->credentials->pluck('name')->toArray(), $actual['credentials']);

            $this->assertArrayHasKey('code_of_conduct', $actual);
            if (is_array($actual['code_of_conduct'])) {
                $this->assertArrayHasKey('status', $actual['code_of_conduct']);
            }
        }
    }


    private function assertExpertPanelInMessage($message)
    {
        $this->assertEquals([
            'uuid' => $this->expertPanel->group->uuid,
            'name' => $this->expertPanel->group->name,
            'description' => $this->expertPanel->group->description,
            'status' => $this->expertPanel->group->groupStatus->name,
            'type' => $this->expertPanel->group->fullType->name,
            'excerpt' => $this->expertPanel->group->excerpt,
            'coi' => url('/coi-group/'.$this->expertPanel->group->uuid),
            'visibility' => optional($this->expertPanel->group->groupVisibility)->name,
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
                'gcep_final_approval' => $this->expertPanel->step_1_approval_date,
                'inactive_date' => null,
                // The fixture group has neither awards nor publications.
                'funding_awards' => [],
            ],
            'status_date' => $this->expertPanel->group->groupStatus->updated_at->toISO8601String(),
            'publications' => [],
        ], $message['data']['group']);
    }
}
