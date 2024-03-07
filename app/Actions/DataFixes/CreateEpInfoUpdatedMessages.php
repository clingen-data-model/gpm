<?php

namespace App\Actions\DataFixes;

use App\Models\Activity;
use Illuminate\Log\Logger;
use Illuminate\Console\Command;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Log;
use App\Modules\Group\Models\GroupType;
use Illuminate\Database\Query\JoinClause;
use App\DataExchange\Models\StreamMessage;
use Lorisleiva\Actions\Concerns\AsCommand;
use Illuminate\Database\Eloquent\Collection;
use App\DataExchange\MessageFactories\DxMessageFactory;

class CreateEpInfoUpdatedMessages
{
    use AsCommand;

    public string $commandSignature = 'data-fix:ep-info-messages';
        
    public function __construct(
        private DxMessageFactory $messageFactory
    ){}

    public function handle():void
    {
        config('dx.push-enable', false);

        $activities = $this->getActivities();
        
        $activities->each(function($activity) {
            $message = $this->makeStreamMessage($activity);
            $message->save();
        });

        config('dx.push-enable', true);
    }

    /**
     * Execute the handler as an artisan command
     */
    public function asCommand(Command $command): void
    {
        $this->handle();
    }
    
    /**
     * Make a stream message for the given activity
     * Set the created_at and updated_at to the activity's created_at and updated_at
     */
    private function makeStreamMessage(Activity $activity): StreamMessage
    {
        $name = $this->resolveName($activity);
        $affiliationId = $this->resolveAffiliationId($activity);

        $streamMessage = new StreamMessage([
            'topic' => config('dx.topics.outgoing.gpm-general-events'),
            'message' => $this->messageFactory->make(
                eventType: 'ep_info_updated',
                message: [
                    'expert_panel' => [
                        'id' => $activity->subject->uuid,
                        'name' => $name,
                        'type' => $activity->subject->fullType->name,
                        'affiliation_id' => $affiliationId
                    ],
                ],
                date: $activity->created_at,
            )
        ]);
        $streamMessage->created_at = $activity->created_at;
        $streamMessage->updated_at = $activity->created_at;
        
        return $streamMessage;
    }

    /**
     * Get the name of the group from the activity properties if it exists
     * Otherwise, get it from the group
     */
    private function resolveName(Activity $activity):string
    {
        if ($activity->properties->has('new_long_base_name')) {
            return $this->buildName(
                $activity->properties['new_long_base_name'], 
                $activity->subject->type
            );
        }

        if ($activity->properties->has('long_base_name')) {
            return $this->buildName(
                $activity->properties['long_base_name'], 
                $activity->subject->type
            );
        }

        return $activity->subject->displayName;
    }

    /**
     * Get the affiliation id from the activity properties if it exists
     * Otherwise, get it from the expert panel
     * 
     * @param Activity $activity
     * @return string
     */
    private function resolveAffiliationId(Activity $activity): string
    {
        if (array_key_exists('affiliation_id', $activity->properties->toArray())) {
            return $activity->properties['affiliation_id'];
        }

        return $activity->subject->expertPanel->affiliation_id;
    }
    
    /**
     * Get all activities that are of the type:
     *  - expert-panel-affiliation-id-updated
     *  - expert-panel-attributes-updated AND have a long_base_name property
     *  - expert-panel-attributes-updated AND have a new_long_base_name property
     * AND were created after the expert panel's defintion was approved
     * 
     * @return Collection
     */
    private function getActivities():Collection
    {
        $query = Activity::leftJoin('groups', function(JoinClause $join) {
                $join->on('activity_log.subject_id', '=', 'groups.id')
                    ->where('activity_log.subject_type', '=', Group::class);
            })
            ->join('expert_panels', function(JoinClause $join) {
                $join->on('groups.id', '=', 'expert_panels.group_id');        
            })
            ->whereRaw('`activity_log`.`created_at` > `expert_panels`.`step_1_approval_date`')
            ->where(function ($q) {
                $q->where('activity_type', 'expert-panel-affiliation-id-updated')
                    ->orWhere(function($q) {
                        $q->where('activity_type', 'expert-panel-attributes-updated')
                            ->whereNotNull('activity_log.properties->long_base_name');
                    })
                    ->orWhere(function($q) {
                        $q->where('activity_type', 'expert-panel-attributes-updated')
                            ->whereNotNull('activity_log.properties->new_long_base_name');
                    })
                ;
            })
            ->with('subject', 'subject.expertPanel');

        return $query->get();
    }

    private function buildName($name, GroupType $type):string
    {
        if (substr($name, -3) === 'CEP') {
            return $name;
        }
        $typeName = $type ? $type->name : '';
        return $name.' '.strtoupper($typeName);
    }
    
}