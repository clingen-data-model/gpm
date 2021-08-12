<?php

namespace App\Modules\ExpertPanel\Jobs;

use App\Modules\ExpertPanel\Exceptions\PersonNotContactException;
use App\Modules\Person\Models\Person;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;

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
        $application = ExpertPanel::findByUuidOrFail($this->applicationUuid);
        $person = Person::findByUuidOrFail($this->personUuid);

        $application->removeContact($person);
    }
}
