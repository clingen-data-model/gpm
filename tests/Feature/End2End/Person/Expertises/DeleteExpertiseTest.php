<?php

namespace Tests\Feature\End2End\Person\Expertises;

use Tests\TestCase;
use App\Models\Expertise;
use Illuminate\Testing\TestResponse;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;

/**
 * @group expertises
 */
class DeleteExpertiseTest extends TestCase
{
    use FastRefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->expertise = Expertise::factory()
                            ->has(Person::factory())
                            ->create();
        $this->person = $this->expertise->people->first();
    }

    /**
     * @test
     */
    public function guest_cannot_delete_a_expertise()
    {
        $this->makeRequest()
            ->assertStatus(401);
    }

    /**
     * @test
     */
    public function unpermissioned_user_cannot_delete_a_expertise()
    {
        $this->login();
        $this->makeRequest()
            ->assertStatus(403);
    }

    /**
     * @test
     */
    public function permissioned_user_can_delete_a_expertise()
    {
        $this->login(permissions: ['people-manage']);
        $this->makeRequest()
            ->assertStatus(200);

        $this->assertDatabaseMissing('expertises', [
            'id' => $this->expertise->id
        ]);

        $this->assertDatabaseMissing('expertise_person', [
            'expertise_id' => $this->expertise->id,
            'person_id' => $this->person->id
        ]);
    }



    private function makeRequest($data = null): TestResponse
    {
        $data = $data ?? [];

        return $this->json('DELETE', '/api/expertises/'.$this->expertise->id, $data);
    }
}
