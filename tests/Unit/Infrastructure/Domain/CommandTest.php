<?php

namespace Tests\Unit\Infrastructure\Domain;

use PHPUnit\Framework\TestCase;
use Infrastructure\Domain\Command;
use App\Domain\Application\Commands\InitiateApplication;

class CommandTest extends TestCase
{
    /**
     * @test
     */
    public function validates_input_array()
    {
        $this->expectException(\ArgumentCountError::class);

        $bad_data = [
            'aggregateId' => '1',
            'workingName' => 'Working Name of EP',
            'cdwgUuid' => 'abc',
        ];
        $cmd = new InitiateApplication(...$bad_data);

        $goodData = array_merge($bad_data, ['ep_type_id' => 1]);
        $cmd = new InitiateApplication(...$goodData);

        $this->assertInstanceOf(Command::class, $cmd);
    }
    
}
