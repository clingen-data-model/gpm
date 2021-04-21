<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder as BaseSeeder;

abstract class Seeder extends BaseSeeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    abstract public function run();

    public function seedFromConfig($config, $modelClass)
    {
        Model::unguard();
        $items = config($config);
        foreach ($items as $slug => $value) {
            if (is_integer($value)) {
                $modelClass::updateOrCreate([
                  'id' => $value,
                  'name' => ucfirst(preg_replace('/-/', ' ', $slug)),
                ]);
            }

            if (is_array($value)) {
                $modelClass::updateOrCreate($value);
            }
        }
        Model::reguard();
    }

    public function seedFromArray($items, $modelClass)
    {
        Model::unguard();
        foreach ($items as $itemData) {
            $modelClass::updateOrCreate(['id'=>$itemData['id']], $itemData);
        }
        Model::reguard();
    }
}
