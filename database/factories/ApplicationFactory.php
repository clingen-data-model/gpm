<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\Models\Cdwg;
use Ramsey\Uuid\Uuid;
use App\Modules\Application\Models\Application;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApplicationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Application::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $cdwg = Cdwg::all()->random();
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'working_name' => uniqid().' Application',
            'cdwg_id' => $cdwg->id,
            'ep_type_id' => 1,
            'date_initiated' => Carbon::now(),
            'current_step' => 1,
            'coi_code' => bin2hex(random_bytes(12))
        ];
    }

    public function gcep()
    {
        return $this->state(function (array $attributes) {
            return [
                'ep_type_id' => config('expert_panels.types.gcep.id')
            ];
        });
    }
    
    public function vcep()
    {
        return $this->state(function (array $attributes) {
            return [
                'ep_type_id' => config('expert_panels.types.vcep.id')
            ];
        });
    }

    public function randomStep()
    {
        return $this->state(function (array $attributes) {
            return [
                'current_step' => $this->faker->randomElement(range(1,4))
            ];
        });
    }
    
}
