<?php

namespace App\Modules\Person\Jobs;

use Illuminate\Support\Facades\DB;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Person\Events\PersonCreated;

class CreatePerson
{
    use Dispatchable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        private string $uuid,
        private string $first_name,
        private string $last_name,
        private string $email,
        private string $phone,
    )
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
        DB::transaction(function () {
            $person = Person::create([
                'uuid' => $this->uuid,
                'first_name' => $this->first_name, 
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
            ]);
            Event::dispatch(new PersonCreated($person));
        });

    }
}
