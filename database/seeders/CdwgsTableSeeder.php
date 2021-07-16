<?php

namespace Database\Seeders;

use App\Models\Cdwg;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class CdwgsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $names = [
            'Neurodevelopmental Disorders CDWG',
            'Hearing Loss CDWG',
            'Cardiovascular CDWG',
            'Inborn Errors Metabolism CDWG',
            'Hereditary Cancer CDWG',
            'Hemostasis/Thrombosis CDWG',
            'RASopathy CDWG',
            'Gene Curation Working Group',
            'Neuromuscular CDWG',
            'Actionability',
            'External curation groups',
            'Kidney Disease CDWG',
            'Skeletal Disorders CDWG',
            'Ocular CDWG',
            'Immunology CDWG',
            'Neurodegenerative',
            'Somatic CDWG',
            'Pulmonary CDWG',
            'Other'
        ];
        foreach ($names as $name) {
            Cdwg::updateOrCreate(
                ['name' => $name],
                ['uuid' => Uuid::uuid4()]
            );
        }
    }
}
