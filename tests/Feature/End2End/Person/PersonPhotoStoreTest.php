<?php

namespace Tests\Feature\End2End\Person;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonPhotoStoreTest extends TestCase
{
    use RefreshDatabase;
    use TestEventPublished;

    public function setup():void
    {
        parent::setup();
        $this->person = Person::factory()->create();
        $this->fakeFile = UploadedFile::fake()->image('beans.jpg');
        $this->user = $this->setupUser(permissions: ['people-manage']);
        Sanctum::actingAs($this->user);
    }

    /**
     * @test
     */
    public function unprivileged_users_cannot_upload_profile_photos()
    {
        $this->user->revokePermissionTo('people-manage');

        $this->makeRequest()
            ->assertStatus(403);

        Storage::disk('profile-photos')->assertMissing($this->fakeFile->hashName());

    }

    /**
     * @test
     */
    public function user_w_people_manage_can_upload_photo()
    {
        $this->makeRequest()
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function person_can_upload_their_own_profile_photo()
    {
        $this->user->revokePermissionTo('people-manage');
        $this->person->update(['user_id' => $this->user->id]);

        $this->makeRequest()
            ->assertStatus(200);
    }

    /**
     * @test
     */
    public function stores_photo_and_updates_person()
    {
        $this->makeRequest()
            ->assertStatus(200);

        Storage::disk('profile-photos')->assertExists($this->fakeFile->hashName());

        $this->assertDatabaseHas('people', [
            'id' => $this->person->id,
            'profile_photo' => $this->fakeFile->hashName()
        ]);
    }

    /**
     * @test
     */
    public function publishses_updated_event_to_gpm_person_events()
    {
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertEventPublished(
            'gpm-person-events',
            'updated',
            $this->person->fresh()
        );
    }


    /**
     * @test
     */
    public function validates_data()
    {
        $this->makeRequest([])
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'profile_photo' => ['This is required.']
                ]
            ]);

            $fakePdf = UploadedFile::fake()->create('beans.pdf', 2100, 'application/pdf');

            $this->makeRequest(['profile_photo' => $fakePdf])
            ->assertStatus(422)
            ->assertJson([
                'errors' => [
                    'profile_photo' => [
                        'The profile photo may not be greater than 2000 kilobytes.',
                        'The profile photo must be a file of type: jpeg, jpg, gif, png.',
                    ]
                ]
            ]);
    }



    private function makeRequest($data = null)
    {
        $data = $data ?? [
            'profile_photo' => $this->fakeFile
        ];

        Storage::fake('public/profile-photos');
        return $this->json('POST', '/api/people/'.$this->person->uuid.'/profile-photo', $data);
    }


}
