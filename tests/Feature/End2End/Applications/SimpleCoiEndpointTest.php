<?php

namespace Tests\Feature\End2End\Applications;

use Carbon\Carbon;
use App\Models\Coi;
use Tests\TestCase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleCoiEndpointTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->application = Application::factory()->create();
    }
    
    /**
     * @test
     */
    public function can_get_application_from_coi_code()
    {
        $this->json('GET', '/api/coi/'.$this->application->coi_code.'/application')
        ->assertStatus(200)
        ->assertJson($this->application->toArray());
    }
    
    /**
     * @test
     */
    public function returns_404_if_application_not_found_for_code()
    {
        $this->json('GET', '/api/coi/some-fake-code/application')
        ->assertStatus(404);
    }
    
    /**
     * @test
     */
    public function validates_coi_response_data()
    {
        $this->json('POST', '/api/coi/'.$this->application->coi_code, [])
            ->assertStatus(422)
            ->assertJsonFragment(['email' => ['This field is required.']])
            ->assertJsonFragment(['first_name' => ['This field is required.']])
            ->assertJsonFragment(['last_name' => ['This field is required.']])
            ->assertJsonFragment(['work_fee_lab' => ['This field is required.']])
            ->assertJsonFragment(['contributions_to_gd_in_ep' => ['This field is required.']])
            ->assertJsonFragment(['independent_efforts' => ['This field is required.']])
            ->assertJsonFragment(['coi' => ['This field is required.']]);

        $this->json('POST', '/api/coi/'.$this->application->coi_code, ['contributions_to_gd_in_ep' => 1])
            ->assertStatus(422)
            ->assertJsonFragment(['contributions_to_genes' => ['This field is required.']]);

        $this->json('POST', '/api/coi/'.$this->application->coi_code, ['email' => 'bob'])
            ->assertStatus(422)
            ->assertJsonFragment(['email' => ['The email must be a valid email address.']]);
    }
    
    /**
     * @test
     */
    public function stores_valid_coi_response()
    {
        $data = [
            'email' => 'elenor@medplace.com',
            'first_name' => 'elenor',
            'last_name' => 'shelstrop',
            'work_fee_lab' => 0,
            'contributions_to_gd_in_ep' => 1,
            'contributions_to_genes' => 'beans',
            'independent_efforts' => 'None',
            'coi' => 'no coi',
        ];

        $this->json('POST', '/api/coi/'.$this->application->coi_code, $data)
            ->assertStatus(200);

        $this->assertDatabaseHas('cois', [
            'application_id' => $this->application->id,
            'data' => json_encode($data)
        ]);
    }
    
    /**
     * @test
     */
    public function returns_csv_of_coi_results_for_application()
    {
        Coi::factory(2)->create(['application_id' => $this->application->id]);
        Carbon::setTestNow('2021-06-01');
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $response = $this->get('/report/'.$this->application->coi_code);
        $response->assertStatus(200);
        
        $this->assertFileExists('/tmp/'.Str::kebab($this->application->name).'-coi-report-'.Carbon::now()->format('Y-m-d').'.csv');

        $response->assertDownload();
    }
}
