<?php

namespace App\Modules\Person\Models;

use Illuminate\Database\Eloquent\Model;
use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $abbreviation
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Institution extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'name',
        'abbreviation',
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
        return new InstitutionFactory();
    }
}
