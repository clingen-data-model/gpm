<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Email;
use App\Actions\FixEmailAddressFormat;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FixEmailAddressStructureTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    public function setup():void
    {
        parent::setup();
        $this->address = $this->faker->email;
        $this->name = $this->faker->name;
        $this->email = Email::factory()->oldAddressStructure()->create(['to' => [$this->address => $this->name]]);
    }

    /**
     * @test
     */
    public function updates_address_structure()
    {
        $this->artisan('dev:update-stored-email');

        $expectedTo = [['name' => $this->name,'address' => $this->address,]];

        $this->assertDatabaseHas('emails', [
            'id' => $this->email->id,
            'to' => json_encode($expectedTo),
        ]);
    }

    /**
     * @test
     */
    public function does_not_update_address_structure_if_already_up_to_date()
    {
        $email = Email::factory()->create();

        $this->artisan('dev:update-stored-email');

        $this->assertDatabaseHas('emails', [
            'id' => $email->id,
            'to' => json_encode($email->to)
        ]);
    }
}
