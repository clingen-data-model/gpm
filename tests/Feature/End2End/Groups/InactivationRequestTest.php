<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use App\Models\DocumentType;
use App\Modules\Group\Models\{Group, GroupMember, ExpertPanelInactivationRequest};
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage, Event, Notification};
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class InactivationRequestTest extends TestCase
{
    private $panel;
    private $actor;
    private string $url;

    public function setUp(): void
    {
        parent::setUp();
        $this->setupForGroupTest();
        $this->setupRoles('chair', 'group');
        $this->setupRoles('grant-liaison', 'group');
        $this->setupRoles(['super-user', 'super-admin']);
        foreach (['groups-manage', 'ep-applications-manage', 'ep-applications-comment', 'ep-applications-approve'] as $permission) {
            $this->setupPermission($permission);
        }
        $this->actor = $this->setupUserWithPerson();
        $this->panel = ExpertPanel::factory()->gcep()->create();
        GroupMember::factory()->create(['group_id' => $this->panel->group_id, 'person_id' => $this->actor->person->id])->assignRole('coordinator');
        $this->url = '/api/groups/'.$this->panel->group->uuid.'/inactivation-request';
        DocumentType::firstOrCreate(['id' => 100], ['name' => 'miscellaneous', 'long_name' => 'miscellaneous', 'is_versioned' => false, 'application_document' => false]);
        Storage::fake('local');
        Sanctum::actingAs($this->actor);
    }

    private function start(): array
    {
        return $this->postJson($this->url)->assertSuccessful()
            ->assertJsonPath('data.status', 'draft')->assertJsonPath('data.can_edit', true)->json('data');
    }

    private function completeAnswers(array $data): array
    {
        $receiver = Group::factory()->create(['group_type_id' => config('groups.types.gcep.id'), 'group_status_id' => config('groups.statuses.active.id')]);
        $liaison = GroupMember::factory()->create(['group_id' => $this->panel->group_id]);
        $receivingLiaison = GroupMember::factory()->create(['group_id' => $receiver->id]);
        return array_replace($data['answers'], [
            'liaisons' => [['member_id' => $liaison->id]], 'publications_acknowledged' => true,
            'receiving_group_id' => $receiver->id, 'receiving_name' => $receiver->name,
            'receiving_liaisons' => [['member_id' => $receivingLiaison->id]],
            'transfer_attestation' => true, 'public_text' => 'This panel has completed its initial scope.',
        ]);
    }

    #[Test]
    public function coordinator_can_start_and_save_one_snapshot_without_changing_publications(): void
    {
        $publication = $this->panel->group->publications()->create(['source' => 'pmid', 'identifier' => '123', 'meta' => ['title' => 'Original title']]);
        $data = $this->start();
        $this->assertSame($data['uuid'], $this->start()['uuid']);
        $this->assertSame('Original title', $data['answers']['publications'][0]['title']);
        $answers = $data['answers'];
        $answers['publications'][0]['title'] = 'Request-only title';
        $answers['publications'][] = ['title' => 'Missing publication', 'pmid' => null, 'included' => true, 'pmc_compliant' => null];
        $this->putJson($this->url, ['version' => 1, 'answers' => $answers, 'status' => 'approved'])->assertOk()->assertJsonPath('data.status', 'draft');
        $this->assertSame('Original title', $publication->fresh()->meta['title']);
        $this->assertSame(1, $this->panel->group->publications()->count());
        $this->assertSame(1, ExpertPanelInactivationRequest::count());
        $this->putJson($this->url, ['version' => 1, 'answers' => $answers])->assertStatus(409);
    }

    #[Test]
    public function submissions_validate_required_fields_and_reports(): void
    {
        $data = $this->start();
        $this->postJson($this->url.'/submit', ['version' => 1])->assertUnprocessable()->assertJsonValidationErrors(['liaisons', 'receiving_group_id', 'public_text', 'transfer_attestation']);
        $answers = $this->completeAnswers($data);
        $this->putJson($this->url, ['version' => 1, 'answers' => $answers])->assertOk();
        $this->postJson($this->url.'/submit', ['version' => 2])->assertUnprocessable()->assertJsonValidationErrors('final_report');
    }

    #[Test]
    public function submitted_request_and_attachments_are_immutable_without_status_events(): void
    {
        $data = $this->start();
        $answers = $this->completeAnswers($data);
        $this->putJson($this->url, ['version' => 1, 'answers' => $answers])->assertOk();
        $upload = $this->postJson($this->url.'/documents', ['version' => 2, 'file' => UploadedFile::fake()->create('final.pdf', 10, 'application/pdf')])->assertOk();
        $doc = $upload->json('data.documents.0');
        $status = $this->panel->group->group_status_id;
        Event::fake([\App\Modules\Group\Events\GroupStatusUpdated::class, \App\Modules\Group\Events\ApplicationStepSubmitted::class]);
        Notification::fake();
        $this->postJson($this->url.'/submit', ['version' => 3])->assertOk()->assertJsonPath('data.status', 'submitted')->assertJsonPath('data.can_edit', false);
        $this->assertSame($status, $this->panel->group->fresh()->group_status_id);
        Event::assertNotDispatched(\App\Modules\Group\Events\GroupStatusUpdated::class);
        Event::assertNotDispatched(\App\Modules\Group\Events\ApplicationStepSubmitted::class);
        Notification::assertNothingSent();
        $this->assertNotNull(ExpertPanelInactivationRequest::first()->submitted_at);
        $this->get($doc['download_url'])->assertOk();
        $this->actor->assignRole('super-user');
        $this->putJson($this->url, ['version' => 4, 'answers' => $answers])->assertForbidden();
        $this->postJson($this->url.'/submit', ['version' => 4])->assertForbidden();
        $this->deleteJson($this->url.'/documents/'.$doc['uuid'], ['version' => 4])->assertForbidden();
        $this->postJson($this->url.'/documents', ['version' => 4, 'file' => UploadedFile::fake()->create('other.pdf', 10, 'application/pdf')])->assertForbidden();
        $document = \App\Models\Document::where('uuid', $doc['uuid'])->first();
        $this->assertFalse($this->actor->can('update', $document));
        $this->assertFalse($this->actor->can('delete', $document));
        $this->putJson('/api/groups/'.$this->panel->group->uuid.'/documents/'.$doc['uuid'], ['document_type_id' => 100, 'filename' => 'changed'])->assertNotFound();
        $applicationDocument = '/api/applications/'.$this->panel->uuid.'/documents/'.$doc['uuid'];
        $this->putJson($applicationDocument, ['date_received' => '2026-11-10', 'notes' => 'changed'])->assertNotFound();
        $this->postJson($applicationDocument.'/final')->assertNotFound();
        ExpertPanelInactivationRequest::first()->update(['status' => 'approved', 'approved_at' => now()]);
        $this->getJson($this->url)->assertOk()->assertJsonPath('data.status', 'approved')->assertJsonPath('data.can_edit', false);
    }

    #[Test]
    public function unrelated_users_and_application_edit_alone_cannot_manage_requests(): void
    {
        $this->start();
        $user = $this->setupUserWithPerson();
        Sanctum::actingAs($user);
        $this->getJson($this->url)->assertForbidden();
        $this->postJson($this->url)->assertForbidden();
        $member = GroupMember::factory()->create(['group_id' => $this->panel->group_id, 'person_id' => $user->person->id]);
        $this->setupPermission('application-edit', 'group');
        $member->givePermissionTo('application-edit');
        $this->getJson($this->url)->assertOk();
        $this->postJson($this->url)->assertForbidden();
        $this->putJson($this->url, ['version' => 1, 'answers' => []])->assertForbidden();
        $member->assignRole('coordinator');
        $this->postJson($this->url)->assertOk();
        $member->update(['end_date' => now()]);
        $this->postJson($this->url)->assertForbidden();
    }

    #[Test]
    public function only_ep_types_can_start_and_variant_panels_require_no_transfer_acknowledgement(): void
    {
        $this->actor->assignRole('super-user');
        foreach (['vcep', 'scvcep'] as $type) {
            $group = Group::factory()->create(['group_type_id' => config("groups.types.$type.id"), 'affiliation_id' => $type === 'vcep' ? '50048' : '50049']);
            $url = '/api/groups/'.$group->uuid.'/inactivation-request';
            $data = $this->postJson($url)->assertSuccessful()->json('data');
            $liaison = GroupMember::factory()->create(['group_id' => $group->id]);
            $answers = array_replace($data['answers'], ['publications_acknowledged' => true, 'transfer_planned' => 'no', 'liaisons' => [['member_id' => $liaison->id]], 'public_text' => 'Panel is closing.']);
            $this->putJson($url, ['version' => 1, 'answers' => $answers])->assertOk();
            $this->postJson($url.'/submit', ['version' => 2])->assertUnprocessable()->assertJsonValidationErrors('no_transfer_attestation');
            $answers['no_transfer_attestation'] = true;
            $this->putJson($url, ['version' => 2, 'answers' => $answers])->assertOk();
            $this->postJson($url.'/documents', ['version' => 3, 'file' => UploadedFile::fake()->create('final.pdf', 10, 'application/pdf')])->assertOk();
            $this->postJson($url.'/submit', ['version' => 4])->assertOk()->assertJsonPath('data.status', 'submitted');
        }
        $group = Group::factory()->create(['group_type_id' => config('groups.types.wg.id')]);
        $this->postJson('/api/groups/'.$group->uuid.'/inactivation-request')->assertForbidden();
    }

    #[Test]
    public function report_access_and_cross_request_deletion_are_scoped(): void
    {
        $this->start();
        $doc = $this->postJson($this->url.'/documents', ['version' => 1, 'file' => UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')])->assertOk()->json('data.documents.0');
        $this->get($doc['download_url'])->assertOk();
        Sanctum::actingAs($this->setupUserWithPerson());
        $this->get($doc['download_url'])->assertForbidden();
        Sanctum::actingAs($this->actor);
        $this->actor->assignRole('super-user');
        \Illuminate\Support\Facades\Cache::forget('user-'.$this->actor->id.'-allPermissions');
        $this->actor = $this->actor->fresh();
        Sanctum::actingAs($this->actor);
        $other = ExpertPanel::factory()->gcep()->create()->group;
        $url = '/api/groups/'.$other->uuid.'/inactivation-request';
        $this->postJson($url)->assertSuccessful();
        $this->deleteJson($url.'/documents/'.$doc['uuid'], ['version' => 1])->assertNotFound();
        $this->deleteJson($this->url.'/documents/'.$doc['uuid'], ['version' => 2])->assertOk()->assertJsonCount(0, 'data.documents');
    }

    #[Test]
    public function receiving_panel_and_member_references_are_validated(): void
    {
        $data = $this->start();
        $answers = $this->completeAnswers($data);
        $answers['receiving_group_id'] = $this->panel->group_id;
        $answers['liaisons'][0]['member_id'] = GroupMember::factory()->create()->id;
        $this->putJson($this->url, ['version' => 1, 'answers' => $answers])->assertUnprocessable()->assertJsonValidationErrors(['receiving_group_id', 'liaisons.0.member_id']);
        $answers = $this->completeAnswers($data);
        $answers['publications'] = [['title' => 'A paper', 'included' => true, 'pmc_compliant' => null]];
        $this->putJson($this->url, ['version' => 1, 'answers' => $answers])->assertOk();
        $this->postJson($this->url.'/submit', ['version' => 2])->assertUnprocessable()->assertJsonValidationErrors('publications_acknowledged');
    }

    #[Test]
    public function invalid_uploads_and_malformed_answers_do_not_mutate_drafts(): void
    {
        $data = $this->start();
        $this->postJson($this->url.'/documents', ['version' => 1, 'file' => UploadedFile::fake()->create('script.html', 1, 'text/html')])->assertUnprocessable();
        $this->postJson($this->url.'/documents', ['version' => 1, 'file' => UploadedFile::fake()->create('huge.pdf', 20481, 'application/pdf')])->assertUnprocessable();
        $answers = $data['answers'];
        $answers['liaisons'] = [['name' => 'Contact', 'email' => 'invalid-email']];
        $this->putJson($this->url, ['version' => 1, 'answers' => $answers])->assertUnprocessable()->assertJsonValidationErrors('liaisons.0.email');
        $this->assertSame(1, ExpertPanelInactivationRequest::first()->version);
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    #[Test]
    public function draft_model_has_relationships_and_database_uniqueness(): void
    {
        $this->start();
        $item = ExpertPanelInactivationRequest::first();
        $this->assertTrue($item->creator->is($this->actor));
        $this->assertTrue($item->group->is($this->panel->group));
        $this->assertTrue($this->panel->group->inactivationRequest->is($item));
        $this->assertIsArray($item->answers);
        $this->expectException(\Illuminate\Database\QueryException::class);
        ExpertPanelInactivationRequest::create(['group_id' => $this->panel->group_id, 'answers' => []]);
    }

    #[Test]
    public function selectors_include_former_liaisons_and_only_compatible_active_receivers(): void
    {
        $former = GroupMember::factory()->create(['group_id' => $this->panel->group_id, 'end_date' => now()]);
        $receiver = Group::factory()->create(['group_type_id' => config('groups.types.gcep.id'), 'group_status_id' => config('groups.statuses.active.id')]);
        $contact = GroupMember::factory()->create(['group_id' => $receiver->id]);
        $incompatible = Group::factory()->create(['group_type_id' => config('groups.types.vcep.id'), 'group_status_id' => config('groups.statuses.active.id')]);
        $data = $this->getJson($this->url.'/options?receiving_group_id='.$receiver->id)->assertOk()->json();
        $this->assertContains($former->id, array_column($data['members'], 'id'));
        $this->assertContains($contact->id, array_column($data['receiving_members'], 'id'));
        $this->assertContains($receiver->id, array_column($data['receiving_groups'], 'id'));
        $this->assertNotContains($incompatible->id, array_column($data['receiving_groups'], 'id'));
        $this->getJson($this->url.'/options?receiving_group_id='.$incompatible->id)->assertUnprocessable();
    }

    #[Test]
    public function only_coordinators_and_super_roles_can_perform_every_draft_mutation(): void
    {
        $data = $this->start();
        $document = $this->postJson($this->url.'/documents', ['version' => 1, 'file' => UploadedFile::fake()->create('final.pdf', 10, 'application/pdf')])->assertOk()->json('data.documents.0');
        foreach (['chair', 'member', 'groups-manage', 'other-coordinator', 'retired-coordinator'] as $kind) {
            $user = $this->setupUserWithPerson();
            $member = GroupMember::factory()->create(['group_id' => $this->panel->group_id, 'person_id' => $user->person->id]);
            if ($kind === 'chair') $member->assignRole('chair');
            if ($kind === 'groups-manage') $user->givePermissionTo('groups-manage');
            if ($kind === 'other-coordinator') GroupMember::factory()->create(['person_id' => $user->person->id])->assignRole('coordinator');
            if ($kind === 'retired-coordinator') { $member->assignRole('coordinator'); $member->update(['end_date' => now()]); }
            Sanctum::actingAs($user);
            if ($kind !== 'retired-coordinator') $this->getJson($this->url)->assertOk()->assertJsonPath('can_start', false)->assertJsonPath('data.can_edit', false);
            $this->postJson($this->url)->assertForbidden();
            $this->putJson($this->url, ['version' => 2, 'answers' => $data['answers']])->assertForbidden();
            $this->postJson($this->url.'/submit', ['version' => 2])->assertForbidden();
            $this->postJson($this->url.'/documents', ['version' => 2, 'file' => UploadedFile::fake()->create('other.pdf', 10, 'application/pdf')])->assertForbidden();
            $this->deleteJson($this->url.'/documents/'.$document['uuid'], ['version' => 2])->assertForbidden();
            $this->getJson($this->url.'/options')->assertForbidden();
            $this->deleteJson($this->url, ['uuid' => $data['uuid'], 'version' => 2])->assertForbidden();
        }
        foreach (['super-user', 'super-admin'] as $role) {
            $user = $this->setupUserWithPerson();
            $user->assignRole($role);
            Sanctum::actingAs($user);
            $group = Group::factory()->gcep()->create();
            $url = '/api/groups/'.$group->uuid.'/inactivation-request';
            $created = $this->postJson($url)->assertSuccessful()->assertJsonPath('data.can_edit', true)->json('data');
            $this->putJson($url, ['version' => 1, 'answers' => $created['answers']])->assertOk();
            $this->getJson($url.'/options')->assertOk();
        }
    }

    #[Test]
    public function active_grant_liaison_defaults_are_initial_suggestions_only(): void
    {
        $this->actor->assignRole('super-user');
        foreach ([0, 1, 2] as $count) {
            $group = Group::factory()->gcep()->create();
            $url = '/api/groups/'.$group->uuid.'/inactivation-request';
            $liaisons = collect();
            for ($i = 0; $i < $count; $i++) $liaisons->push(GroupMember::factory()->create(['group_id' => $group->id])->assignRole('grant-liaison'));
            GroupMember::factory()->create(['group_id' => $group->id, 'end_date' => now()])->assignRole('grant-liaison');
            GroupMember::factory()->create(['group_id' => $group->id])->assignRole('chair');
            $data = $this->postJson($url)->assertSuccessful()->assertJsonCount($count, 'data.answers.liaisons')->json('data');
            foreach ($data['answers']['liaisons'] as $contact) {
                $this->assertSame('Grant Liaison', $contact['role']);
                $this->assertNotNull($contact['person_id']);
            }
            $answers = $data['answers'];
            $answers['liaisons'] = [];
            $this->putJson($url, ['version' => 1, 'answers' => $answers])->assertOk()->assertJsonCount(0, 'data.answers.liaisons');
            $this->getJson($url)->assertOk()->assertJsonCount(0, 'data.answers.liaisons');
            $this->postJson($url)->assertSuccessful()->assertJsonCount(0, 'data.answers.liaisons');
            foreach ($liaisons as $member) $this->assertTrue($member->fresh()->hasRole('grant-liaison'));
        }
    }

    #[Test]
    public function legacy_contact_is_normalized_without_overwriting_saved_choices(): void
    {
        $data = $this->start();
        $answers = $data['answers'];
        unset($answers['liaisons']);
        $answers['liaison'] = ['name' => 'Legacy manual', 'email' => 'legacy@example.org'];
        $item = ExpertPanelInactivationRequest::first();
        $item->update(['schema_version' => 1, 'answers' => $answers]);
        GroupMember::factory()->create(['group_id' => $this->panel->group_id])->assignRole('grant-liaison');
        $this->getJson($this->url)->assertOk()->assertJsonPath('data.answers.liaisons.0.name', 'Legacy manual');
        $this->assertArrayHasKey('liaison', $item->fresh()->answers); // GET does not rewrite stored history.
        $this->putJson($this->url, ['version' => 1, 'answers' => $answers])->assertOk()->assertJsonCount(1, 'data.answers.liaisons');
        $this->assertSame(2, $item->fresh()->schema_version);
        $this->assertArrayNotHasKey('liaison', $item->fresh()->answers);
    }

    #[Test]
    public function multiple_contacts_and_receiving_details_are_synchronized_and_preserved(): void
    {
        $data = $this->start();
        $answers = $this->completeAnswers($data);
        $receiver = Group::find($answers['receiving_group_id']);
        $parent = Group::factory()->cdwg()->create();
        $receiver->update(['parent_id' => $parent->id, 'affiliation_id' => '59999']);
        $contact = GroupMember::factory()->create(['group_id' => $receiver->id])->assignRole('grant-liaison');
        $parentContact = GroupMember::factory()->create(['group_id' => $parent->id])->assignRole('grant-liaison');
        $suggestions = $this->getJson($this->url.'/options?receiving_group_id='.$receiver->id)->assertOk()->json('receiving_liaison_suggestions');
        $this->assertSame([$contact->id], array_column($suggestions, 'member_id'));
        $this->assertNotContains($parentContact->id, array_column($suggestions, 'member_id'));
        $own = GroupMember::factory()->create(['group_id' => $this->panel->group_id])->assignRole('grant-liaison');
        $answers['liaisons'][] = ['member_id' => $own->id, 'person_id' => 99999, 'name' => 'Spoofed', 'email' => 'wrong@example.org'];
        $answers['receiving_liaisons'][] = ['member_id' => $contact->id, 'name' => 'Wrong', 'email' => 'wrong@example.org'];
        $answers['receiving_name'] = 'Spoofed receiver';
        $saved = $this->putJson($this->url, ['version' => 1, 'answers' => $answers])->assertOk()
            ->assertJsonPath('data.answers.receiving_name', $receiver->display_name)
            ->assertJsonPath('data.answers.receiving_affiliation_id', '59999')
            ->assertJsonPath('data.answers.receiving_parent_name', $parent->display_name)
            ->assertJsonPath('data.answers.liaisons.1.person_id', $own->person_id)
            ->assertJsonPath('data.answers.liaisons.1.email', $own->person->email)
            ->assertJsonCount(2, 'data.answers.receiving_liaisons')->json('data');
        $this->getJson($this->url)->assertOk()->assertJsonPath('data.answers', $saved['answers']);
        $duplicate = $saved['answers'];
        $duplicate['liaisons'][] = $duplicate['liaisons'][0];
        $this->putJson($this->url, ['version' => 2, 'answers' => $duplicate])->assertUnprocessable()->assertJsonValidationErrors('liaisons.2.email');
        $this->postJson($this->url.'/documents', ['version' => 2, 'file' => UploadedFile::fake()->create('final.pdf', 10, 'application/pdf')])->assertOk();
        $this->postJson($this->url.'/submit', ['version' => 3])->assertOk();
    }

    #[Test]
    public function no_transfer_preserves_hidden_answers_but_has_no_workflow_receiver(): void
    {
        $this->panel->group->update(['group_type_id' => config('groups.types.vcep.id'), 'affiliation_id' => '51111']);
        $data = $this->start();
        $answers = $this->completeAnswers($data);
        $receiver = Group::find($answers['receiving_group_id']);
        $receiver->update(['group_type_id' => config('groups.types.scvcep.id'), 'affiliation_id' => '52222']);
        $answers['transfer_planned'] = 'yes';
        $answers['transfer_discussion_date'] = '2026-11-01';
        $saved = $this->putJson($this->url, ['version' => 1, 'answers' => $answers])->assertOk()->json('data.answers');
        $saved['transfer_planned'] = 'no';
        $saved['no_transfer_attestation'] = true;
        $this->putJson($this->url, ['version' => 2, 'answers' => $saved])->assertOk()
            ->assertJsonPath('data.receiving_group_id', null)->assertJsonPath('data.answers.receiving_liaisons', $saved['receiving_liaisons']);
        $this->getJson($this->url)->assertOk()->assertJsonPath('data.answers.receiving_group_id', $receiver->id);
        $saved['transfer_planned'] = 'yes';
        $this->putJson($this->url, ['version' => 3, 'answers' => $saved])->assertOk()->assertJsonPath('data.receiving_group_id', $receiver->id);
    }

    #[Test]
    public function authorized_roles_can_permanently_delete_drafts_and_their_files_then_start_again(): void
    {
        foreach (['coordinator', 'super-user', 'super-admin'] as $role) {
            $actor = $this->actor;
            if ($role !== 'coordinator') {
                $actor = $this->setupUserWithPerson();
                $actor->assignRole($role);
            }
            Sanctum::actingAs($actor);
            $data = $this->start();
            $first = $this->postJson($this->url.'/documents', ['version' => 1, 'file' => UploadedFile::fake()->create('old.pdf', 10, 'application/pdf')])->assertOk()->json('data.documents.0');
            $this->deleteJson($this->url.'/documents/'.$first['uuid'], ['version' => 2])->assertOk();
            $this->postJson($this->url.'/documents', ['version' => 3, 'file' => UploadedFile::fake()->create('final.pdf', 10, 'application/pdf')])->assertOk();
            $item = ExpertPanelInactivationRequest::first();
            $documents = $item->documents()->withTrashed()->get();
            $this->assertCount(2, $documents);
            foreach ($documents as $document) Storage::disk('local')->assertExists($document->storage_path);

            // A group-owned document must not be touched by request deletion.
            $path = 'documents/unrelated-'.$role.'.pdf';
            Storage::disk('local')->put($path, 'Unrelated report');
            $other = $this->panel->group->documents()->create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(), 'filename' => 'unrelated.pdf',
                'storage_path' => $path, 'document_type_id' => 100,
            ]);
            $this->deleteJson($this->url, ['uuid' => $data['uuid'], 'version' => 4])->assertOk()->assertJsonPath('can_start', true);
            $this->assertDatabaseMissing('expert_panel_inactivation_requests', ['id' => $item->id]);
            foreach ($documents as $document) {
                Storage::disk('local')->assertMissing($document->storage_path);
                $this->assertDatabaseMissing('documents', ['id' => $document->id]);
            }
            Storage::disk('local')->assertExists($path);
            $this->assertDatabaseHas('documents', ['id' => $other->id]);
            $this->getJson($this->url)->assertOk()->assertJsonPath('data', null)->assertJsonPath('can_start', true);
            $replacement = $this->start();
            $this->assertNotSame($data['uuid'], $replacement['uuid']);
            $this->assertSame([], $replacement['documents']);
            // A stale tab cannot delete the replacement even when its version matches.
            $this->deleteJson($this->url, ['uuid' => $data['uuid'], 'version' => 1])->assertStatus(409);
            $this->deleteJson($this->url, ['uuid' => $replacement['uuid'], 'version' => 1])->assertOk();
        }
    }

    #[Test]
    public function deletion_is_forbidden_in_every_non_draft_status_even_for_super_roles(): void
    {
        $data = $this->start();
        $item = ExpertPanelInactivationRequest::first();
        foreach (['coordinator', 'super-user', 'super-admin'] as $role) {
            $user = $this->actor;
            if ($role !== 'coordinator') { $user = $this->setupUserWithPerson(); $user->assignRole($role); }
            Sanctum::actingAs($user);
            foreach (['submitted', 'revision_requested', 'pending_oc_review', 'approved'] as $status) {
                $item->update(['status' => $status]);
                $this->getJson($this->url)->assertOk()->assertJsonPath('data.can_delete', false);
                $this->deleteJson($this->url, ['uuid' => $data['uuid'], 'version' => 1])->assertForbidden();
                $this->assertDatabaseHas('expert_panel_inactivation_requests', ['id' => $item->id, 'status' => $status]);
            }
        }
    }

    #[Test]
    public function stale_deletion_preserves_the_draft_and_attachments(): void
    {
        $data = $this->start();
        $this->postJson($this->url.'/documents', ['version' => 1, 'file' => UploadedFile::fake()->create('final.pdf', 10, 'application/pdf')])->assertOk();
        $item = ExpertPanelInactivationRequest::first();
        $document = $item->documents()->first();
        $this->deleteJson($this->url, ['uuid' => $data['uuid'], 'version' => 1])->assertStatus(409);
        Storage::disk('local')->assertExists($document->storage_path);
        $this->assertDatabaseHas('expert_panel_inactivation_requests', ['id' => $item->id]);
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    #[Test]
    public function storage_cleanup_failure_does_not_delete_database_records(): void
    {
        $data = $this->start();
        $this->postJson($this->url.'/documents', ['version' => 1, 'file' => UploadedFile::fake()->create('final.pdf', 10, 'application/pdf')])->assertOk();
        $item = ExpertPanelInactivationRequest::first();
        $document = $item->documents()->first();
        $disk = \Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);
        $disk->shouldReceive('exists')->with($document->storage_path)->once()->andReturn(true);
        $disk->shouldReceive('delete')->with($document->storage_path)->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        $this->deleteJson($this->url, ['uuid' => $data['uuid'], 'version' => 2])->assertStatus(500);
        $this->assertDatabaseHas('expert_panel_inactivation_requests', ['id' => $item->id, 'status' => 'draft']);
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }
}
