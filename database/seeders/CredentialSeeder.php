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
                'synonyms' => ['certified genetic counselor', 'genetic counselor', 'lcgc', 'licensed, certified genetic counselor']
            ],
            [
                'id' => 2,
                'name' => 'MD',
                'approved' => 1,
                'synonyms' => ['doctor', 'medical degree', 'medical doctor']
            ],
            [
                'id' => 3,
                'name' => 'MSc',
                'approved' => 1,
                'synonyms' => ['ms', 'masters', 'master of science', 'masters\'s of science',]
            ],
            [
                'id' => 4,
                'name' => 'PhD',
                'approved' => 1,
                'synonyms' => ['postdoc', 'doctorate', 'doctor of philosophy', 'ph d', 'post-doctoral']
            ],
            [
                'id' => 5,
                'name' => 'BA',
                'approved' => 1,
                'synonyms' => ['bachelor of arts', 'bachelor']
            ],
            [
                'id' => 6,
                'name' => 'BSc',
                'approved' => 1,
                'synonyms' => ['bachelor of science', 'bs', 'bachelor']
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
                'synonyms' => ['fellow, american college of medical genetics']
            ],
            [
                'id' => 9,
                'name' => 'PharmD',
                'approved' => 1,
                'synonyms' => ['pharm d',]
            ],
            [
                'id' => 10,
                'name' => 'VMD',
                'approved' => 1,
                'synonyms' => ['veterinary medicines', 'directorate', 'doctor', 'doctorate']
            ],
            [
                'id' => 11,
                'name' => 'MBBCh',
                'approved' => 1,
                'synonyms' => ['bachelor degree of medicine and surgery', 'bachelor of medicine and surgery', 'MBChB']
            ],
            [
                'id' => 12,
                'name' => 'None',
                'approved' => 1,
                'synonyms' => ['na']
            ],
            [
                'id' => 13,
                'name' => 'MHS',
                'approved' => 1,
                'synonyms' => ['MHSc', 'masters of health science', 'master\'s of health science', 'masters']
            ]

        ];

        foreach ($creds as $cred) {
            $synonyms = $cred['synonyms'];
            unset($cred['synonyms']);
            $credential = Credential::updateOrCreate(['id' => $cred['id']], $cred);
            if ($synonyms) {
                $credential->addSynonyms($synonyms);
            }
        }
    }
}
