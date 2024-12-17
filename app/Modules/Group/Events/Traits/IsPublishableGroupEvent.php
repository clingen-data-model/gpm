<?php

namespace App\Modules\Group\Events\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

trait IsPublishableGroupEvent
{
    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function getEventType(): string
    {
        return Str::snake(self::class);
    }

    public function getPublishableMessage(): array
    {
        $message = [
            'group' => [
                    'id' => $this->group->uuid,
                    'name' => $this->group->name,
                    'status' => $this->group->groupStatus->name,
                    'parent_group' => $this->group->parentGroup?->name, // TODO: check representation when no parent: null or empty string?
                    'type' => $this->group->fullType->name,
            ],
        ];
        if ($this->group->isEp) { // TODO: consider moving this to IsPublishableExpertPanelEvent
            $message['group']['affiliation_id'] = $this->group->expertPanel->affiliation_id;
            $message['group']['scope_description'] = $this->group->expertPanel->scope_description;
            $message['group']['short_name'] = $this->group->expertPanel->short_base_name;
            // TODO: not sure about these fields
            // $message['group']['long_base_name'] = $this->group->expertPanel->long_base_name;
            // $message['group']['hypothesis_group'] = $this->group->expertPanel->hypothesis_group;
            // $message['group']['membership_description'] = $this->group->expertPanel->membership_description;
            if ($this->group->fullType->name === 'vcep') {
                $message['group']['cspec_url'] = $this->group->expertPanel->affiliation_id;
            }
        }
        return $message;
    }
}
