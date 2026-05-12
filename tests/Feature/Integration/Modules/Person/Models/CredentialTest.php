<?php

namespace Tests\Feature\Integration\Modules\Person\Models;

use Tests\TestCase;
use App\Models\Credential;
use App\ControlledVocabularies\Synonym;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\ControlledVocabularies\HasSynonymInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('credentials')]
class CredentialTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function implements_HasSynonymsInterface()
    {
        $cred = Credential::factory()->make();
        $this->assertTrue(implementsInterface($cred, HasSynonymInterface::class));
    }

    #[Test]
    public function can_add_synonyms()
    {
        $cred = Credential::factory()->create();
        $cred->addSynonyms('test');

        $this->assertEquals('test', $cred->synonyms->pluck('name')->first());


        $cred = Credential::factory()->create();
        $cred->addSynonyms(['elenor', 'chidi']);

        $this->assertEquals(['elenor', 'chidi'], $cred->synonyms->pluck('name')->toArray());
    }

    #[Test]
    public function only_adds_a_synonym_once()
    {
        $cred = Credential::factory()->create();
        $cred->addSynonyms(['elenor', 'chidi']);

        $cred->addSynonyms(['elenor', 'tehani']);
        $this->assertEquals(3, $cred->synonyms()->count());
    }


    #[Test]
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
