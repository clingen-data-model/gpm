<?php

namespace App\Domain\Application\Models;

use DateTime;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\ApplicationFactory;
use App\Domain\Application\Events\ContactAdded;
use App\Domain\Application\Events\StepApproved;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Domain\Application\Events\ApplicationInitiated;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Application extends Model
{
    use HasFactory;
    use HasTimestamps;

    protected $fillable = [
        'uuid',
        'working_name',
        'cdwg_id',
        'ep_type_id',
        'date_initiated',
        'current_step',
        'survey_monkey_url',
    ];

    protected $dates = [
        'date_initiated'
    ];

    protected $casts = [
        'ep_type_id' => 'int',
        'cdwg_id' => 'int',
        'current_step' => 'int',
        'approval_dates'=> 'json'
    ];

    // Domain methods
    public static function initiate(string $uuid, string $working_name, int $cdwg_id, int $ep_type_id, DateTime $date_initiated)
    {
        $application = static::create([
            'uuid' => $uuid,
            'working_name' => $working_name,
            'cdwg_id' => $cdwg_id,
            'ep_type_id' => $ep_type_id,
            'date_initiated' => $date_initiated,
        ]);

        Event::dispatch(new ApplicationInitiated($application));

        return $application;
    }

    public function addContact(Person $contact)
    {
        $this->contacts()->attach($contact);
        Event::dispatch(new ContactAdded($this, $contact));
    }

    public function approveCurrentStep(Carbon $dateApproved)
    {
        $approvedStep = $this->current_step;
        $this->current_step = $this->current_step+1;
        if (is_null($this->approval_dates)) {
            $this->approval_dates = [];
        }

        $this->addApprovalDate($approvedStep, $dateApproved);
        
        $this->save();

        Event::dispatch(new StepApproved(application: $this, step: $approvedStep, dateApproved: $dateApproved));
    }
    

    private function addApprovalDate(int $step, Carbon $date)
    {
        $approvalDates = $this->approval_dates;
        $approvalDates['step '.$step] = $date;
        $this->approval_dates = $approvalDates;
    }

    // Queries
    static public function findByUuid($uuid)
    {
        return static::where('uuid', $uuid)->first();
    }

    static public function findByUuidOrFail($uuid)
    {
        $application = static::where('uuid', $uuid)->first();
        if (is_null($application)) {
            throw new ModelNotFoundException();
        }

        return $application;
        
    }

    // Relationships
    public function contacts()
    {
        return $this->belongsToMany(Person::class);
    }
    

    static protected function newFactory()
    {
        return new ApplicationFactory();
    }


}
