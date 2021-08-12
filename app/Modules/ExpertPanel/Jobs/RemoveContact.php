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
    public function __construct(private string $expertPanelUuid, private string $personUuid)
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
        $expertPanel = ExpertPanel::findByUuidOrFail($this->expertPanelUuid);
        $person = Person::findByUuidOrFail($this->personUuid);

        $expertPanel->removeContact($person);
    }
}
