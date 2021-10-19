<?php

namespace Database\Seeders;

use Database\Seeders\Seeder;
use App\Modules\Person\Models\Institution;
use Ramsey\Uuid\Uuid;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $uuids = [];
        for ($i=0; $i < 5; $i++) {
            $uuids[] = Uuid::uuid4();
        }

        $items = [
            ['uuid'=> $uuids[0], 'id' => 1, 'name' => 'UNC Chapel Hill'],
            ['uuid'=> $uuids[1], 'id' => 2, 'name' => 'Broad'],
            ['uuid'=> $uuids[2], 'id' => 3, 'name' => 'Stanford'],
            ['uuid'=> $uuids[3], 'id' => 4, 'name' => 'Giesinger'],
            ['uuid'=> $uuids[4], 'id' => 5, 'name' => 'Baylor Medical College'],
        ];

        $this->seedFromArray($items, Institution::class);
    }
}
