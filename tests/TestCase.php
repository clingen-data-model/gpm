<?php

namespace Tests;

use Illuminate\Support\Carbon;
use App\Models\Cdwg;
use Ramsey\Uuid\Uuid;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use WithFaker;
    // Helper methods
    
    public function setup():void
    {
        parent::setup();
    }
    

    public function makeApplicationData()
    {
        $data = [
            'uuid' => Uuid::uuid4()->toString(),
            'working_name' => 'EP Working Name',
            'ep_type_id' => config('expert_panels.types.vcep.id'),
            'cdwg_id' => Cdwg::all()->random()->id,
            'date_initiated' => Carbon::parse('2020-01-01')
        ];
        return $data;
    }

    protected function makeContactData(int $number = 1)
    {
        $contacts = [];
        for ($i=0; $i < $number; $i++) { 
            $contacts[] = [
                'uuid' => Uuid::uuid4(),
                'first_name' => $this->faker()->firstName,
                'last_name' => $this->faker()->lastName,
                'email' => $this->faker()->email,
                'phone' => $this->faker()->phoneNumber
            ];
        }

        return $contacts;
    }
}
