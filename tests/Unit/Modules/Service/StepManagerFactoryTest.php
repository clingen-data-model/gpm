<?php

namespace Tests\Unit\Modules\Service;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Modules\ExpertPanel\Service\StepManagerFactory;
use App\Modules\ExpertPanel\Service\Steps\VcepDraftStepManager;
use App\Modules\ExpertPanel\Service\Steps\VcepPilotStepManager;
use App\Modules\ExpertPanel\Service\Steps\VcepFinalizeStepManager;
use App\Modules\ExpertPanel\Service\Steps\GcepDefinitionStepManager;
use App\Modules\ExpertPanel\Service\Steps\VcepDefinitionStepManager;
use App\Modules\ExpertPanel\Exceptions\UnexpectedCurrentStepException;

class StepManagerFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function setup():void
    {
        parent::setup();
        $this->seed();
        $this->vcepTypeId = 2;
        $this->gcepTypeId = 1;
    }

    /**
     * @test
     */
    public function returns_GcepDefinitionStepManager_if_gcep_and_step_1()
    {
        $this->returnsManagerForTypeAndStep(GcepDefinitionStepManager::class, $this->gcepTypeId, 1);
    }
    
    /**
     * @test
     */
    public function returns_VcepDefinitionStepManager_if_vcep_and_step_1()
    {
        $this->returnsManagerForTypeAndStep(VcepDefinitionStepManager::class, $this->vcepTypeId, 1);
    }
    
    /**
     * @test
     */
    public function returns_VcepDraftStepManager_if_vcep_and_step_2()
    {
        $this->returnsManagerForTypeAndStep(VcepDraftStepManager::class, $this->vcepTypeId, 2);
    }
    
    /**
     * @test
     */
    public function returns_VcepPilotStepManager_if_vcep_and_step_3()
    {
        $this->returnsManagerForTypeAndStep(VcepPilotStepManager::class, $this->vcepTypeId, 3);
    }
    
    /**
     * @test
     */
    public function returns_VcepFinalizeStepManager_if_vcep_and_step_3()
    {
        $this->returnsManagerForTypeAndStep(VcepFinalizeStepManager::class, $this->vcepTypeId, 4);
    }
    

    /**
     * @test
     */
    public function throws_UnexpectedStepException_if_step_cant_be_matched()
    {
        $factory = new StepManagerFactory();    
        $expertPanel = new ExpertPanel([
            'current_step' => 5,
            'ep_type_id' => 2,
            'cdwg_id' => 1,
            'date_initiated' => Carbon::now()
        ]);
        
        $this->expectException(UnexpectedCurrentStepException::class);
        $factory($expertPanel);

    }

    private function returnsManagerForTypeAndStep($expectedClass, $epTypeId, $currentStep)
    {
        $factory = new StepManagerFactory();
        $expertPanel = ExpertPanel::factory()->make(['ep_type_id' => $epTypeId, 'current_step' => $currentStep]);
        $stepManager = $factory($expertPanel);

        $this->assertInstanceOf($expectedClass, $stepManager);
    }
    
    
    
}
