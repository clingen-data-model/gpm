<?php

namespace Tests\Feature\Integration\Modules\Person\Models;

use Tests\TestCase;
use App\Models\Credential;
use App\ControlledVocabularies\Synonym;
use Illuminate\Foundation\Testing\WithFaker;
use Plannr\Laravel\FastRefreshDatabase\Traits\FastRefreshDatabase;
use App\ControlledVocabularies\HasSynonymInterface;

/**
 * @group credentials
 */
class CredentialTest extends TestCase
{
    use FastRefreshDatabase;

    /**
     * @test
     */
    public function implements_HasSynonymsInterface()
    {
        $cred = Credential::factory()->make();
        $this->assertTrue(implementsInterface($cred, HasSynonymInterface::class));
    }

    /**
     * @test
     */
    public function can_add_synonyms()
    {
        $cred = Credential::factory()->create();
        $cred->addSynonyms('test');

        $this->assertEquals('test', $cred->synonyms->pluck('name')->first());


        $cred = Credential::factory()->create();
        $cred->addSynonyms(['elenor', 'chidi']);

        $this->assertEquals(['elenor', 'chidi'], $cred->synonyms->pluck('name')->toArray());
    }

    /**
     * @test
     */
    public function only_adds_a_synonym_once()
    {
        $cred = Credential::factory()->create();
        $cred->addSynonyms(['elenor', 'chidi']);

        $cred->addSynonyms(['elenor', 'tehani']);
        $this->assertEquals(3, $cred->synonyms()->count());
    }


    /**
     * @test
     */
    public function can_search_synonyms()
    {
        $cred1 = Credential::factory()->create();
        $cred1->addSynonyms(['elli', 'blah', 'blub']);

        $credUnderTest = Credential::factory()->create();

        $credUnderTest->addSynonyms(['Elenor', 'chidi', 'tehani']);

        $this->assertEquals(2, Credential::matchesSynonym('el')->count());

        $this->assertEquals(1, Credential::matchesSynonym('chidi')->count());
        $this->assertEquals(1, Credential::matchesSynonym('C.H.I.D.I')->count());
    }



}
