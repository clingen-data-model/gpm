<?php

namespace Infrastructure\Adapters\Models;

use Infrastructure\Domain\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoredEvent extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'data' => 'array'
    ];

    public function scopeForAggregateId($query, $uuid)
    {
        return $query->where('aggregate_id', $uuid);
    }
    
    public function toDomainEvent(): Event
    {
        $class = $this->type;
        $eventArgs = array_merge(['aggregateId' => $this->aggregate_id], $this->data);
        return new $class(...$eventArgs);
    }
    
    static public function getLatestVersionForAggregate($aggregateId)
    {
        return (int)static::forAggregateId($aggregateId)
            ->selectRaw('max(aggregate_version) as latest_stored_version')
            ->first()
            ?->latest_stored_version;
    }

    static public function fromDomainEvent(Event $domainEvent, int $version): StoredEvent
    {        
        $aggregateId = $domainEvent->aggregateId;
        $data = $domainEvent->toArray();
        unset($data['aggregateId']);

        return new static([
            'type' => get_class($domainEvent),
            'aggregate_id' => $aggregateId,
            'aggregate_version' => $version,
            'data' => $data
        ]);
    }
    
}
