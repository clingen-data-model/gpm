<?php

namespace Tests\Feature\Integration\Modules\Application\Models;

use Tests\TestCase;
use App\Models\Document;
use App\Modules\ExpertPanel\Models\NextAction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Events\ApplicationCompleted;
use App\Modules\ExpertPanel\Events\ExpertPanelAttributesUpdated;

/**
 * @group applications
 * @group expert-panels
 */
class ExpertPanelTest extends TestCase
{
    use RefreshDatabase;
    
    public function setup():void
    {
        parent::setup();
        $this->seed();
        Carbon::setTestNow('2021-01-01');
    }
    
    /**
     * @test
     */
    public function name_is_working_name_if_long_base_name_is_null()
    {
        $expertPanel = ExpertPanel::factory()->create(['long_base_name' => null]);

        $this->assertEquals($expertPanel->name, $expertPanel->working_name);
    }

    /**
     * @test
     */
    public function name_is_long_base_name_if_not_null()
    {
        $expertPanel = ExpertPanel::factory()->create(['long_base_name' => 'Beans']);

        $this->assertEquals($expertPanel->name, $expertPanel->long_base_name);
    }
    
    /**
     * @test
     */
    public function appends_clingen_url_based_on_affiliation_id()
    {
        $expertPanel = ExpertPanel::factory()->make();

        $this->assertNull($expertPanel->clingen_url);

        $expertPanel->affiliation_id = '4000123';

        $this->assertEquals('https://clinicalgenome.org/affiliation/4000123', $expertPanel->clingen_url);
    }

    /**
     * @test
     */
    public function can_get_first_scope_document()
    {
        $expertPanel = ExpertPanel::factory()->create();
        $document1 = Document::factory()
                        ->make([
                            'document_type_id' => config('documents.types.scope.id'),
                            'version' => 1
                        ]);
        $document2 = Document::factory()
                        ->make([
                        'document_type_id' => config('documents.types.scope.id'),
                        'version' => 2
                    ]);
        
        $expertPanel->group->documents()->save($document1);
        $expertPanel->group->documents()->save($document2);

        $this->assertEquals($expertPanel->group->documents->count(), 2);
        $this->assertEquals($expertPanel->firstScopeDocument->id, $document1->id);
    }
    
    /**
     * @test
     */
    public function can_get_first_final_document()
    {
        $expertPanel = ExpertPanel::factory()->create();
        $document1 = Document::factory()
                        ->make([
                            'document_type_id' => config('documents.types.final-app.id'),
                            'version' => 1
                        ]);
        $document2 = Document::factory()
                        ->make([
                        'document_type_id' => config('documents.types.final-app.id'),
                        'version' => 2
                    ]);
        
        $expertPanel->group->documents()->save($document1);
        $expertPanel->group->documents()->save($document2);

        $this->assertEquals($expertPanel->group->documents->count(), 2);

        $this->assertEquals($expertPanel->firstFinalDocument->id, $document1->id);
    }

    /**
     * @test
     */
    public function removes_epType_display_name_suffix_when_storing_long_and_short_base_name()
    {
        $expertPanel = ExpertPanel::factory()->make();

        $expertPanel->long_base_name = 'Garbage Gut VCEP';
        $expertPanel->short_base_name = 'GG VCEP';

        $this->assertEquals('Garbage Gut', $expertPanel->getAttributes()['long_base_name']);
    }
}
