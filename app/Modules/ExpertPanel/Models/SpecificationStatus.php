<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\SpecificationStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class SpecificationStatus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'event',
        'description',
        'color',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public static function findByEvent(string $event): ?SpecificationStatus
    {
        return self::where('event', $event)->first();
    }

    protected static function newFactory()
    {
        return new SpecificationStatusFactory();
    }

}
