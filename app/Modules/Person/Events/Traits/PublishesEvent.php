<?php

namespace App\Modules\Person\Events\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Modules\Group\Events\GroupCheckpointEvent;

/**
 * Base methods for making a person event publishable
 */
trait PublishesEvent
{
    public function getEventType(): string
    {
        return Str::snake(self::class);
    }

    public function getTopic(): string
    {
        return config('dx.topics.outgoing.gpm-person-events');
    }

    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function shouldPublish(): bool
    {
        return true;
    }

    public function getPublishableMessage(): array
    {
        return [
            'person' => [
                'id' => $this->person->uuid,
                'first_name' => $this->person->first_name,
                'last_name' => $this->person->last_name,
                'email' => $this->person->email,
                'institution' => $this->getInstitutionMessage(),
                'credentials' => $this->person->credentials,
                'biography' => $this->person->biography,
                'profile_photo' => $this->person->ProfilePhotoUrl,
                'orcid_id' => $this->person->orcid_id,
                'hypothesis_id' => $this->person->hypothesis_id,
                'timezone' => $this->person->timezone
            ]
        ];
    }

    private function getInstitutionMessage(): ?array
    {
        if (!$this->person->institution_id) {
            return null;
        }

        return [
            'id' => $this->person->institution->uuid,
            'website_id' => $this->person->institution->website_id,
            'name' => $this->person->institution->name,
            'abbreviation' => $this->person->institution->abbreviation,
            'url' => $this->person->institution->url,
            'address' => $this->person->institution->address,
            'country' => $this->person->institution->country,
        ];
    }

    public function checkpointIfNeeded(): void
    {
        // For now, we don't have checkpointing for people, but will need
        // to checkpoint groups in which the person is a member.

        // FIXME: not all person events need to trigger a checkpoint
        //      (but maybe all publishable ones do?)
        if ($this->shouldPublish()) {
            foreach ($this->person->groups as $g) {
                event(new GroupCheckpointEvent($g));
            }
        }
    }


}
