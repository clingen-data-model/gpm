<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Permission::class;

    /**
     * permissions has a unique index on (name, guard_name), and faker's word
     * list is small enough that a test creating a handful of permissions
     * collides now and then. faker's unique() would exhaust the list instead:
     * definition() runs even when the caller overrides the name.
     */
    private static int $nameSequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->word.'-'.(++static::$nameSequence),
            'guard_name' => 'web',
        ];
    }
}
