<?php

namespace App\Modules\User\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\PreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $data_type
 * @property string $default
 * @property int $applies_to_role
 * @property int $applies_to_permission
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Preference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'data_type',
        'default',
        'applies_to_role',
        'applies_to_permission',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'applies_to_role' => 'integer',
        'applies_to_permission' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class);
    }

    public function appliesToRole(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AppliesToRole::class);
    }

    public function appliesToPermission(): BelongsTo
    {
        return $this->belongsTo(\App\Models\AppliesToPermission::class);
    }

    public function newFactory()
    {
        return new PreferenceFactory();
    }
}
