<?php

namespace Tests\Feature\End2End\Groups;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\AttestationSigned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\Group\Actions\AttestationGcepStore;
use App\Modules\Group\Models\Group;

class AttestationGcepTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->setupForGroupTest();
        $this->setupPermission('ep-applications-manage');

        $this->user = User::factory()->create();
        $this->expertPanel = ExpertPanel::factory()->gcep()->create();

        Sanctum::actingAs($this->user);
        Carbon::setTestNow('2021-11-14');
    }
    
    /**
     * @test
     */
    public function unprivileged_user_cannot_submit_gcep_attestation()
    {
        $this->submitRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function validates_required_data()
    {
        $this->user->givePermissionTo('ep-applications-manage');

        $this->submitRequest([])
            ->assertStatus(422)
            ->assertJsonFragment([
                'utilize_gt' => ['This is required.'],
            ])
            ->assertJsonFragment([
                'utilize_gci' => ['This is required.'],
            ])
            ->assertJsonFragment([
                'curations_publicly_available' => ['This is required.'],
            ])
            ->assertJsonFragment([
                'pub_policy_reviewed' => ['This is required.'],
            ])
            ->assertJsonFragment([
                'draft_manuscripts' => ['This is required.'],
            ])
            ->assertJsonFragment([
                'recuration_process_review' => ['This is required.'],
            ])
            ->assertJsonFragment([
                'biocurator_training' => ['This is required.'],
            ])
            ->assertJsonFragment([
                'gci_training_date' => ['This is required.'],
            ])
            ->assertJsonFragment([
                'biocurator_mailing_list' => ['This is required.'],
            ]);
    }
    
    /**
     * @test
     */
    public function privileged_user_can_submit_gcep_attestation()
    {
        $this->user->givePermissionTo('ep-applications-manage');

        $this->submitRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'utilize_gt' => 1,
            'utilize_gci' => 1,
            'curations_publicly_available' => 1,
            'pub_policy_reviewed' => 1,
            'draft_manuscripts' => 1,
            'recuration_process_review' => 1,
            'biocurator_training' => 1,
            'gci_training_date' => Carbon::now(),
            'biocurator_mailing_list' => 1,
            'gcep_attestation_date' => Carbon::now()
        ]);
    }

    /**
     * @test
     */
    public function logs_activity()
    {
        $this->user->givePermissionTo('ep-applications-manage');
        
        $this->submitRequest()
            ->assertStatus(200);

        $this->assertLoggedActivity(
            subject: $this->expertPanel->group,
            description: 'GCEP attestation submitted by '.$this->user->name.' on '.Carbon::now()->format('Y-m-d').' at '.Carbon::now()->format('H:i:s').'.',
            activity_type: 'gcep-attestation-submitted',
            logName: 'groups'
        );
    }

    /**
     * @test
     */
    public function does_not_update_gcep_attestation_fields_if_already_completed()
    {
        $this->user->givePermissionTo('ep-applications-manage');

        $this->expertPanel->gcep_attestation_date = '2015-01-01 00:00:00';
        $this->expertPanel->save();

        $this->submitRequest()
            ->assertStatus(200);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'gcep_attestation_date' => '2015-01-01 00:00:00'
        ]);

        $this->assertDatabaseMissing('activity_log', [
            'subject_type' => Group::class,
            'subject_id' => $this->expertPanel->group_id,
            'activity_type' => 'attestation-signed'
        ]);
    }
    

    /**
     * NOTE:
     * Arguably these last three tests belong in a different file because they test the handler, not the endpoint.
     * I chose to add them here b/c all other tests that check this action are in this file.
     */

    /**
     * @test
     */
    public function handler_does_not_set_gcep_attestation_date_if_not_all_fields_are_true()
    {
        $action = new AttestationGcepStore();

        $action->handle($this->expertPanel->group, [
            'utilize_gt' => true,
            'utilize_gci' => true,
            'curations_publicly_available' => true,
            'pub_policy_reviewed' => true,
            'draft_manuscripts' => true,
            'recuration_process_review' => true,
            'biocurator_training' => true,
            'biocurator_mailing_list' => false,
            'gci_training_date' => '2021-01-01 00:00:00',
        ]);

        $this->assertDatabaseHas('expert_panels', [
            'id' => $this->expertPanel->id,
            'utilize_gt' => true,
            'utilize_gci' => true,
            'curations_publicly_available' => true,
            'pub_policy_reviewed' => true,
            'draft_manuscripts' => true,
            'recuration_process_review' => true,
            'biocurator_training' => true,
            'biocurator_mailing_list' => false,
            'gci_training_date' => '2021-01-01 00:00:00',
            'gcep_attestation_date' => null
        ]);
    }
    
    /**
     * @test
     */
    public function does_not_fire_event_if_not_all_attestation_fields_are_true()
    {
        $action = new AttestationGcepStore();

        Event::fake();
        
        $action->handle($this->expertPanel->group, [
            'utilize_gt' => true,
            'utilize_gci' => true,
            'curations_publicly_available' => true,
            'pub_policy_reviewed' => true,
            'draft_manuscripts' => true,
            'recuration_process_review' => true,
            'biocurator_training' => true,
            'biocurator_mailing_list' => false,
            'gci_training_date' => '2021-01-01 00:00:00',
        ]);

        Event::assertNotDispatched(AttestationSigned::class);
    }
    

    private function submitRequest($data = null)
    {
        $data = $data ?? [
            'utilize_gt' => true,
            'utilize_gci' => true,
            'curations_publicly_available' => true,
            'pub_policy_reviewed' => true,
            'draft_manuscripts' => true,
            'recuration_process_review' => true,
            'biocurator_training' => true,
            'gci_training_date' => Carbon::now(),
            'biocurator_mailing_list' => true,
            'gcep_attestation_date' => Carbon::now()
        ];

        return $this->json('POST', '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/attestations/gcep', $data);
    }
}
