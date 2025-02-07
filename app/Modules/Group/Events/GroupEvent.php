<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Events\RecordableEvent;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Events\PublishableEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class GroupEvent extends RecordableEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group)
    {
    }

    public function hasSubject(): bool
    {
        return true;
    }

    public function getSubject(): Model
    {
        return $this->group;
    }

    public function getLog(): string
    {
        return 'groups';
    }

    public function getProperties(): ?array
    {
        return null;
    }

    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function getTopic(): string
    {
        return config('dx.topics.outgoing.gpm-general-events');
    }

    public function isPublishableGroup(): bool
    {
        // EP events are only publishable after definition is approved
        return !$this->group->isEp || $this->group->expertPanel->definitionIsApproved;
    }

    public function shouldPublish(): bool
    {
        return false;
    }

    public function getEventType(): string
    {
        return Str::snake((new \ReflectionClass($this))->getShortName());
    }

    private function groupRepresentation(Group $g): array {
        // FIXME: this will stack overflow if someone is foolish enough to create a circular reference
        $item = [
            'id' => $this->group->uuid,
            'name' => $this->group->name,
            'description' => $this->group->description,
            'status' => $this->group->groupStatus->name,
            'type' => $this->group->type->name,
        ];
        if ($this->group->parent != null) {
            $item['parent_group'] = $this->group->parent->representationForDataExchange();
        }
        if ($this->group->isEp) {
            $item['ep_id'] = $this->group->expertPanel->uuid;
            $item['affiliation_id'] = $this->group->expertPanel->affiliation_id;
            $item['scope_description'] = $this->group->expertPanel->scope_description;
            $item['short_name'] = $this->group->expertPanel->short_base_name;
            // TODO: not sure about these fields, they appear to be unused
            // $item['long_base_name'] = $this->group->expertPanel->long_base_name;
            // $item['hypothesis_group'] = $this->group->expertPanel->hypothesis_group;
            // $item['membership_description'] = $this->group->expertPanel->membership_description;
            if ($this->group->fullType->name === 'vcep') {
                $item['cspec_url'] = $this->group->expertPanel->affiliation_id;
            }
        }
        return $item;
    }

    public function getPublishableMessage(): array {
        return ['group' => $this->groupRepresentation($this->group)];
    }

    abstract public function getLogEntry() :string;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('group-events');
    }
}
