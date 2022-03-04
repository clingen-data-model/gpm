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
        $this->setupForGroupTest();
    }

    /**
     * @test
     */
    public function returns_GcepDefinitionStepManager_if_gcep_and_step_1()
    {
        $this->returnsManagerForTypeAndStep(GcepDefinitionStepManager::class, config("expert_panels.types.gcep.id"), 1);
    }
    
    /**
     * @test
     */
    public function returns_VcepDefinitionStepManager_if_vcep_and_step_1()
    {
        $this->returnsManagerForTypeAndStep(VcepDefinitionStepManager::class, config('expert_panels.types.vcep.id'), 1);
    }
    
    /**
     * @test
     */
    public function returns_VcepDraftStepManager_if_vcep_and_step_2()
    {
        $this->returnsManagerForTypeAndStep(VcepDraftStepManager::class, config('expert_panels.types.vcep.id'), 2);
    }
    
    /**
     * @test
     */
    public function returns_VcepPilotStepManager_if_vcep_and_step_3()
    {
        $this->returnsManagerForTypeAndStep(VcepPilotStepManager::class, config('expert_panels.types.vcep.id'), 3);
    }
    
    /**
     * @test
     */
    public function returns_VcepFinalizeStepManager_if_vcep_and_step_3()
    {
        $this->returnsManagerForTypeAndStep(VcepFinalizeStepManager::class, config('expert_panels.types.vcep.id'), 4);
    }
    

    /**
     * @test
     */
    public function throws_UnexpectedStepException_if_step_cant_be_matched()
    {
        $factory = new StepManagerFactory();
        $expertPanel = new ExpertPanel([
            'current_step' => 5,
            'expert_panel_type_id' => 2,
            'date_initiated' => Carbon::now()
        ]);
        
        $this->expectException(UnexpectedCurrentStepException::class);
        $factory($expertPanel);
    }

    private function returnsManagerForTypeAndStep($expectedClass, $epTypeId, $currentStep)
    {
        $factory = new StepManagerFactory();
        $expertPanel = ExpertPanel::factory()->make(['expert_panel_type_id' => $epTypeId, 'current_step' => $currentStep]);
        $stepManager = $factory($expertPanel);

        $this->assertInstanceOf($expectedClass, $stepManager);
    }
}
