<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Modules\ExpertPanel\CoiData;
use App\Modules\ExpertPanel\CoiDataCaster;

class CoiDataCasterTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_a_CoiData_object_from_a_json_string()
    {
        $json = json_encode([
            'coi' => 1,
            'coi_detail' => 'bob\'s burgers'
        ]);

        $coiData =  (new CoiDataCaster)->get(null, 'data', $json, ['data' => $json]);

        $this->assertInstanceOf(CoiData::class, $coiData);
        $this->assertEquals(json_decode($json), $coiData->data);
    }

    /**
     * @test
     */
    public function it_converts_a_CoiData_object_to_a_json_string()
    {
        $data = (object)['coi' => 1, 'coi_detail' => 'bob\''];
        $coiData = new CoiData($data);
        
        $jsonString = (new CoiDataCaster)->set(null, 'data', $coiData, ['data' => null]);

        $this->assertEquals(json_encode($data), $jsonString);
    }
}
