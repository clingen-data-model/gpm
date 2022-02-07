<?php

namespace App\DataExchange\Models;

use Database\Factories\IncomingStreamMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingStreamMessage extends Model
{
    use HasFactory;

    public $fillable = [
        'topic',
        'partition',
        'payload',
        'error_code',
        'offset',
        'timestamp',
        'key',
        'processed_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'partition' => 'integer',
        'payload' => 'object',
        'offset' => 'integer',
        'processed_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
    }

    public static function newFactory()
    {
        return new IncomingStreamMessageFactory();
    }
}
