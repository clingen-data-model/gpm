<?php

namespace Tests\Feature\Integration\Modules\Person\Models;

use App\ControlledVocabularies\HasSynonymInterface;
use App\Models\Credential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @group credentials
 */
class CredentialTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function implements_HasSynonymsInterface(): void
    {
        $cred = Credential::factory()->make();
        $this->assertTrue(implementsInterface($cred, HasSynonymInterface::class));
    }

    /**
     * @test
     */
    public function can_add_synonyms(): void
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
    public function only_adds_a_synonym_once(): void
    {
        $cred = Credential::factory()->create();
        $cred->addSynonyms(['elenor', 'chidi']);

        $cred->addSynonyms(['elenor', 'tehani']);
        $this->assertEquals(3, $cred->synonyms()->count());
    }

    /**
     * @test
     */
    public function can_search_synonyms(): void
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
