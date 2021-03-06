<?php

namespace App\Models;

use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    // use CrudTrait;
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'from' => 'array',
        'sender' => 'array',
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'reply_to' => 'array'
    ];

    public function scopeFrom($query, $from)
    {
        return $query->where('from_address', $from);
    }

    public function scopeLikeFrom($query, $from)
    {
        return $query->where('from_address', 'LIKE', '%'.$from.'%');
    }
    
    public function scopeTo($query, $to)
    {
        return $query->where('to', $to);
    }

    public function scopeLikeTo($query, $to)
    {
        return $query->where('to', 'LIKE', '%'.$to.'%');
    }
}
