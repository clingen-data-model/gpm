<?php

namespace App\DataExchange\Models;

use App\DataExchange\Events\Created;
use Database\Factories\StreamMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StreamMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'topic',
        'sent_at',
        'error',
    ];

    protected $dispatchesEvents = [
        'created' => Created::class,
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'id' => 'int',
        'message' => 'array',
    ];

    public function scopeUnsent($query)
    {
        return $query->whereNull('sent_at');
    }

    public function scopeSent($query)
    {
        return $query->whereNotNull('sent_at');
    }

    public function scopeTopic($query, $topic)
    {
        return $query->where('topic', $topic);
    }

    public static function newFactory()
    {
        return new StreamMessageFactory();
    }
}
