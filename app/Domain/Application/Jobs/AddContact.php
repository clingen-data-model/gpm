<?php

namespace App\Domain\Application\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Domain\Application\Models\Person;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Domain\Application\Models\Application;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class AddContact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    private Application $application;
    private Person $person;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $applicationUuid, 
        string $first_name,
        string $last_name,
        string $email,
        string $phone,
    )
    {
        $this->application = Application::findByUuidOrFail($applicationUuid);
        $this->person = Person::firstOrCreate(['email' => $email],[
            'first_name' => $first_name, 
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
        ]);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {        
        $this->application->addContact($this->person);
    }
}
