<?php

namespace Database\Factories;

use Carbon\Carbon;
use App\Models\Cdwg;
use Ramsey\Uuid\Uuid;
use App\Domain\Application\Models\Application;
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
            'uuid' => Uuid::uuid4(),
            'working_name' => uniqid().' Application',
            'cdwg_id' => $cdwg->id,
            'ep_type_id' => 1,
            'date_initiated' => Carbon::now(),
            'current_step' => 1
        ];
    }
}
