<?php

namespace Database\Factories;

use Illuminate\Support\Str;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupType;
use App\Modules\Group\Models\GroupStatus;
use App\Modules\Group\Actions\CoiCodeMake;
use Illuminate\Database\Eloquent\Factories\Factory;
use Database\Factories\Traits\GetsRandomConfigValue;

class GroupFactory extends Factory
{
    use GetsRandomConfigValue;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Group::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $code = app()->make(CoiCodeMake::class)->handle();

        return [
            'uuid' => $this->faker->uuid(),
            'name' => $this->faker->name(),
            'group_type_id' => 1,
            'group_status_id' => $this->getRandomConfigValue('groups.statuses')['id'],
            'coi_code' => $code
        ];
    }

    public function vcep()
    {
        return $this->state(function ($attributes) {
            return [
                'uuid' => $this->faker->uuid,
                'name' => uniqid().' CDWG',
                'group_type_id' => config('groups.types.vcep.id'),
                'group_status_id' => config('groups.statuses.applying.id')
            ];
        });
    }

    public function gcep()
    {
        return $this->state(function ($attributes) {
            return [
                'uuid' => $this->faker->uuid,
                'name' => uniqid().' CDWG',
                'group_type_id' => config('groups.types.gcep.id'),
                'group_status_id' => config('groups.statuses.applying.id')
            ];
        });
    }

    public function cdwg()
    {
        return $this->state(function ($attributes) {
            return [
                'uuid' => $this->faker->uuid,
                'name' => uniqid().' CDWG',
                'group_type_id' => config('groups.types.cdwg.id'),
                'group_status_id' => config('groups.statuses.active.id')
            ];
        });
    }

    public function wg()
    {
        return $this->state(function ($attributes) {
            return [
                'group_type_id' => config('groups.types.wg.id'),
                'group_status_id' => config('groups.statuses.active.id')
            ];
        });
    }
}
