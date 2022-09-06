<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\RulesetStatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RulesetStatus extends Model
{
    use HasFactory;

    protected $table = 'ruleset_statuses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'event',
        'description',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    public static function findByEvent(string $event): ?RulesetStatus
    {
        return self::where('event', $event)->first();
    }

    protected static function newFactory()
    {
        return new RulesetStatusFactory();
    }


}
