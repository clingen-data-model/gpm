<?php

namespace Tests\Unit\Domain\Applications\Aggregates;

use App\Domain\Application\Aggregates\Application;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ApplicationTest extends TestCase
{
    /**
     * @test
     */
    public function creates_a_new_instance_of_itself()
    {
        $uuid = Uuid::uuid4()->toString();
        $cdwgId = uniqid();
        $datetime = Carbon::now();
        $application = Application::initiate(
            aggregateId: $uuid,
            cdwgUuid: $cdwgId,
            epTypeId: 1,
            workingName: 'Test Name',
            dateInitiated: $datetime
        );

        $this->assertInstanceOf(Application::class, $application);
        $this->assertEquals($application->aggregateId, $uuid);
        $this->assertEquals($application->workingName, 'Test Name');
        $this->assertEquals($application->cdwgUuid, $cdwgId);
        $this->assertEquals($application->epTypeId, 1);
        $this->assertEquals($application->dateInitiated, $datetime);
    }

    /**
     * @test
     */
    public function sets_version_to_1_when_creating_itself()
    {
        $application = Application::initiate(
            aggregateId: Uuid::uuid4()->toString(),
            cdwgUuid: uniqid(),
            epTypeId: 1,
            workingName: 'Test Name',
            dateInitiated: Carbon::now()
        );

        $this->assertEquals($application->version, 1);
    }
    
    
}
