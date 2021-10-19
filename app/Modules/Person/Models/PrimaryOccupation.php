<?php

namespace App\Modules\Person\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\PrimaryOccupationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PrimaryOccupation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];


    // Factory
    protected static function newFactory()
    {
        return new PrimaryOccupationFactory();
    }
}
