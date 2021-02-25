<?php

namespace App\Domain\Application\Jobs;

use App\Domain\Application\Exceptions\PersonNotContactException;
use App\Domain\Person\Models\Person;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Domain\Application\Models\Application;

class RemoveContact
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(private string $applicationUuid, private string $personUuid)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $application = Application::findByUuidOrFail($this->applicationUuid);
        $person = Person::findByUuidOrFail($this->personUuid);

        $application->removeContact($person);
    }
}
