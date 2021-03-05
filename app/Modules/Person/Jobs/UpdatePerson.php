<?php

namespace App\Modules\Person\Jobs;

use Illuminate\Support\Facades\DB;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Modules\Person\Events\PersonDataUpdated;

class UpdatePerson
{
    use Dispatchable;

    private Person $person;

    private array $attributes;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $uuid,
        array $attributes
    )
    {
        $this->person = Person::findByUuidOrFail($uuid);
        $this->attributes = array_filter($attributes);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (count($this->attributes) == 0) {
            return;
        }

        DB::transaction(function () {
            $this->person->update($this->attributes);
            Event::dispatch(new PersonDataUpdated($this->person, $this->attributes));
        });
    }
}
