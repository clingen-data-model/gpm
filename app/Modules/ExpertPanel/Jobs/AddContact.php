<?php

namespace App\Modules\ExpertPanel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Modules\Person\Models\Person;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Validation\ValidationException;

class AddContact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $applicationUuid,
        private string $uuid
    )
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {        
        $application = ExpertPanel::findByUuidOrFail($this->applicationUuid);
        
        $person = Person::findByUuidOrFail($this->uuid);

        $application->addContact($person);
    }
}
