<?php

namespace App\Modules\Person\Models;

use App\Modules\Person\Models\Person;
use App\Modules\Person\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\InstitutionFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'name',
        'abbreviation',
        'url',
        'address',
        'country_id',
        'website_id',
        'approved'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'country_id' => 'integer',
        'approved' => 'boolean',
    ];

    /**
     * RELATIONS
     */

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function people()
    {
        return $this->hasMany(Person::class);
    }
    
    
    /**
     * SCOPES
     */
    public function scopeIsApproved($query)
    {
        return $query->where('approved', 1);
    }
    
    public function scopeNeedsApproval($query)
    {
        return $query->where('approved', '!=', 1);
    }
    

    // Factory
    protected static function newFactory()
    {
        return new InstitutionFactory();
    }
}
