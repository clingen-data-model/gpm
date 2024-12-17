<?php

namespace Database\Seeders;

use App\Models\Expertise;
use Illuminate\Database\Seeder;

class ExpertiseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $exps = [
            [
                'id' => 1,
                'name' => 'Basic research scientist',
                'approved' => 1,
                'synonyms' => ['research scientist', 'scientist', 'basic research']
            ],
            [
                'id' => 2,
                'name' => 'Clinical genetic testing laboratorian',
                'synonyms' => ['genetic testing', 'lab', 'lab tech', 'diagnostic laboratory'],
                'approved' => 1
            ],
            [
                'id' => 3,
                'name' => 'Physician',
                'approved' => 1,
                'synonyms' => ['doctor', 'md']
            ],
            [
                'id' => 4,
                'name' => 'Genetic counselor',
                'approved' => 1,
                'synonyms' => ['genetic counseling', 'counseling', 'cgc', 'lcgc']
            ],
            [
                'id' => 5,
                'name' => 'Statistical geneticist or Bioinformatician',
                'approved' => 1,
                'synonyms' => ['bioinformatics', 'analytics', 'data science', 'computation', 'computational']
            ],
            [
                'id' => 6,
                'name' => 'ClinGen Framework Expert',
                'approved' => 1,
                'synonyms' => ['amcg', 'guidelines', 'framework expert']
            ],
            [
                'id' => 7,
                'name' => 'Biocuration',
                'approved' => 1,
                'synonyms' => ['curation', 'bio-curation', 'curator']
            ],
            [
                'id' => 8,
                'name' => 'None',
                'approved' => 1,
                'synonyms' => ['na']
            ],
            [
                'id' => 9,
                'name' => 'Pharmacist or Pharmacogenomicist',
                'approved' => 1,
                'synonyms' => ['pharm', 'pharmacogenomics'],
            ],
        ];

        foreach ($exps as $exp) {
            $synonyms = $exp['synonyms'];
            unset($exp['synonyms']);
            $expertise = Expertise::updateOrCreate(['id' => $exp['id']], $exp);
            if ($synonyms) {
                $expertise->addSynonyms($synonyms);
            }
        }
    }
}
