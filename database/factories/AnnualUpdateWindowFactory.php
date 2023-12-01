<?php

namespace Database\Factories;

use App\Models\AnnualUpdateWindow;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnnualUpdateWindowFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AnnualUpdateWindow::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'start' => Carbon::now()->subDays(30),
            'end' => Carbon::now()->addDays(30),
            'for_year' => '2021',
        ];
    }
}
