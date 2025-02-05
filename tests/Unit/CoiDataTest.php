<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Modules\ExpertPanel\CoiData;

class CoiDataTest extends TestCase
{
    private CoiData $coiData;

    public function setup():void
    {
        parent::setup();
        $this->coiData = new CoiData((object)['coi' => 1, 'coi_detail' => 'Bob\'s Burgers']);
    }

    /**
     * @test
     */
    public function returns_null_if_data_element_not_found()
    {
        $this->assertNull($this->coiData->beans);
    }

    /**
     * @test
     */
    public function returns_value_if_data_element_found()
    {
        $this->assertEquals(1, $this->coiData->coi);
        $this->assertEquals('Bob\'s Burgers', $this->coiData->coi_detail);
    }
}
