<?php

namespace Database\Seeders;

use App\Models\Credential;
use Database\Seeders\Seeder;

class CredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $creds = [
            [
                'id' => 1,
                'name' => 'CGC',
                'approved' => 1,
                'synonyms' => ['certified genetic counselor', 'genetic counselor', 'lcgc']
            ],
            [
                'id' => 2,
                'name' => 'MD',
                'approved' => 1,
                'synonyms' => ['doctor']
            ],
            [
                'id' => 3,
                'name' => 'MSc',
                'approved' => 1,
                'synonyms' => ['ms', 'masters']
            ],
            [
                'id' => 4,
                'name' => 'PhD',
                'approved' => 1,
                'synonyms' => ['postdoc', 'doctorate']
            ],
            [
                'id' => 5,
                'name' => 'BA',
                'approved' => 1,
                'synonyms' => ['bachelor of arts']
            ],
            [
                'id' => 6,
                'name' => 'BSc',
                'approved' => 1,
                'synonyms' => ['bachelor of science', 'bs']
            ],
            [
                'id' => 7,
                'name' => 'MPH',
                'approved' => 1,
                'synonyms' => ['masters of public health']
            ],
            [
                'id' => 8,
                'name' => 'FACMG',
                'approved' => 1,
                'synonyms' => ['Fellow, American College of Medical Genetics']
            ],
            [
                'id' => 9,
                'name' => 'PharmD',
                'approved' => 1,
            ],
            [
                'id' => 10,
                'name' => 'VMD',
                'approved' => 1,
                'synonyms' => ['veterinary medicines', 'directorate', 'doctor', 'doctorate']
            ],
            [
                'id' => 100,
                'name' => 'None',
                'approved' => 1,
                'synonymes' => ['n/a', 'na']
            ]
        ];

        foreach ($creds as $cred) {
            $credential = Credential::updateOrCreate(['id' => $cred['id']], $cred);
            if (isset($cred['synonyms']) && $cred['synonyms']) {
                $credential->addSynonyms($cred['synonyms']);
            }
        }
    }
}
