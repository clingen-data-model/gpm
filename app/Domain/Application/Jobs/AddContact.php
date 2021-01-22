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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private Application $application, 
        private Person $person
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
        $this->application->addContact($this->person);
    }
}
