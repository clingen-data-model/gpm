<?php

namespace App\Console\Commands\Dev;

use App\Models\Credential;
use Illuminate\Console\Command;
use App\Modules\Person\Models\Person;
use App\ControlledVocabularies\Synonym;

class MigrateCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:people:credentials-migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Attempts to populate credentials as well as possible from legacy_credentials.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $people = Person::whereNotNull('legacy_credentials')
                    ->get();

        $credentialsById = Credential::all()->keyBy('id')->all();
        $credentialsIndex = $this->getCredentialsIndex();
        $synonymsIndex = array_filter($this->getSynonymsIndex(), fn($i) => count($i) == 1);

        $progressBar = $this->output->createProgressBar($people->count());

        foreach ($people as $person ) {
            $credentialTokens = $this->tokenize($person->legacy_credentials, array_merge($credentialsIndex, $synonymsIndex));
            $credentials = array_intersect_key($credentialsById, array_flip(array_intersect_key($credentialsIndex, array_flip($credentialTokens))));

            $synonyms = array_intersect_key($synonymsIndex, array_flip($credentialTokens));
            $uniqueSynonymOfIds = collect($synonyms)->values()->flatten()->unique()->flip()->toArray();
            $synonymCredentials = array_intersect_key($credentialsById, $uniqueSynonymOfIds);
            $credentials = array_merge($credentials, $synonymCredentials);

            $person->credentials()->sync(collect($credentials)->pluck('id'));

            $progressBar->advance();
        }

        $progressBar->finish();

        echo "\n";

        return 0;
    }

    private function getCredentialsIndex(): array
    {
        return Credential::all()->keyBy(fn($i) => strtolower($i->name))->map(fn($c) => $c->id)->toArray();
    }

    private function getSynonymsIndex(): array
    {
        return Synonym::forType(Credential::class)->get()
                        ->groupBy(fn($i) => strtolower($i->name))
                        ->map(function ($syn) {
                            return $syn->pluck('synonym_of_id');
                        })
                        ->toArray()
                        ;
    }


    private function tokenize($string, $termIndex)
    {
        $matches = [];
        $pattern = '/'.strtolower(implode('|',array_keys($termIndex))).'/i';
        preg_match_all($pattern, strtolower($string), $matches);
        $words = collect(preg_split('/\s|,/', strtolower($string)));

        $tokens = collect($matches)->flatten()->merge($words)->filter()->unique()->toArray();

        return $tokens;
    }


}
