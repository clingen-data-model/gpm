<?php

namespace Tests\Unit\Domain\Service;

use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Domain\Application\Models\Application;
use App\Domain\Application\Service\StepManagerFactory;
use App\Domain\Application\Exceptions\UnexpectedCurrentStepException;

class StepManagerFactoryTest extends TestCase
{
    public function setup():void
    {
        parent::setup();
    }

    /**
     * @test
     */
    public function throws_UnexpectedStepException_if_step_cant_be_matched()
    {
        $factory = new StepManagerFactory();    
        $application = new Application([
            'current_step' => 5,
            'ep_type_id' => 2,
            'cdwg_id' => 1,
            'date_initiated' => Carbon::now()
        ]);
        
        $this->expectException(UnexpectedCurrentStepException::class);
        $factory($application);

    }
    
    
}
