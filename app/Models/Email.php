<?php

namespace App\Models;

use Backpack\CRUD\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    // use CrudTrait;

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

    /**
     * ACCESSORS
     */

    public function getToForMailableAttribute()
    {
        return $this->convertAddressesForMailable($this->to);
    }

    public function getCcForMailableAttribute()
    {
        return $this->convertAddressesForMailable($this->cc);
    }

    public function getBccForMailableAttribute()
    {
        return $this->convertAddressesForMailable($this->bcc);
    }

    private function convertAddressesForMailable($addresses): array
    {
        $expectedTo = [];
        
        foreach ($addresses as $email => $name) {
            $expectedTo[] = [
                'name' => $name,
                'address' => $email
            ];
        }

        return $expectedTo;
    }
}
