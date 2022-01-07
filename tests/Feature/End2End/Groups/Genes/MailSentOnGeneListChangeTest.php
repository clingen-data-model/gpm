<?php

namespace Tests\Feature\End2End\Groups\Genes;

use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Modules\Group\Mail\GeneAddedMail;
use App\Modules\Group\Mail\GeneRemovedMail;
use Tests\Traits\SeedsHgncGenesAndDiseases;
use Illuminate\Foundation\Testing\WithFaker;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MailSentOnGeneListChangeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsHgncGenesAndDiseases;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->seedGenes();
        $this->seedDiseases();

        $this->user = $this->setupUser(null, ['ep-applications-manage']);

        $this->expertPanel = ExpertPanel::factory()->create(['expert_panel_type_id' => config('expert_panels.types.vcep.id')]);
        $this->url = '/api/groups/'.$this->expertPanel->group->uuid.'/expert-panel/genes';
    }
    
    /**
     * @test
     */
    public function nothing_sent_on_gene_added_to_applying_ep()
    {
        Mail::fake();

        $this->makeAddRequest()
            ->assertStatus(200);
        
        Mail::assertNotSent(GeneAddedMail::class);
    }
    
    /**
     * @test
     */
    public function nothing_sent_on_gene_removed_from_applying_ep()
    {
        Mail::fake();

        $this->gene1 = $this->expertPanel->genes()->create([
            'hgnc_id' => 123345,
            'mondo_id' => 'MONDO:1234567',
            'gene_symbol' => uniqid()
        ]);

        $this->makeRemoveRequest()
            ->assertStatus(200);
        
        Mail::assertNotSent(GeneRemovedMail::class);
    }
    
    /**
     * @test
     */
    public function email_sent_on_gene_added_to_approved_ep()
    {
        $this->expertPanel->step_1_approval_date = Carbon::now();
        $this->expertPanel->save();
        Mail::fake();

        $this->makeAddRequest()
            ->assertStatus(200);
        
        Mail::assertSent(GeneAddedMail::class);
        Mail::assertSent(function (GeneAddedMail $mail) {
            return $mail->group->id == $this->expertPanel->group->id
                && $mail->hasTo(config('mail.from.address'));
        });
    }

    /**
     * @test
     */
    public function email_sent_on_gene_removed_from_approved_ep()
    {
        $this->gene1 = $this->expertPanel->genes()->create([
            'hgnc_id' => 123345,
            'mondo_id' => 'MONDO:1234567',
            'gene_symbol' => uniqid()
        ]);
        $this->expertPanel->step_1_approval_date = Carbon::now();
        $this->expertPanel->save();
        Mail::fake();

        $this->makeRemoveRequest()
            ->assertStatus(200);
        
        Mail::assertSent(GeneRemovedMail::class);
        Mail::assertSent(function (GeneRemovedMail $mail) {
            return $mail->group->id == $this->expertPanel->group->id
                && $mail->hasTo(config('mail.from.address'));
        });
    }



    private function makeAddRequest($data = null)
    {
        Carbon::setTestNow('2022-01-01');
        $this->seedGenes(['hgnc_id' => 789012, 'gene_symbol' => 'ABC12']);
        $this->seedDiseases(['mondo_id' => 'MONDO:8901234', 'name' => 'fartsalot']);

        $genesData = $data ?? [ ['hgnc_id'=>12345, 'mondo_id' => 'MONDO:9876543'] ];

        Sanctum::actingAs($this->user);
        return $this->json('POST', $this->url, ['genes' => $genesData]);
    }

    private function makeRemoveRequest()
    {
        Carbon::setTestNow('2021-11-01');
        Sanctum::actingAs($this->user);
        return $this->json('DELETE', $this->url.'/'.$this->gene1->id)
            ->assertStatus(200);
    }
}
