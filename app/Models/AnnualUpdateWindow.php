<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnnualUpdateWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'for_year',
        'start',
        'end',
    ];

    protected $casts = [
        'id' => 'int',
        'for_year' => 'int',
        'start' => 'datetime',
        'end' => 'datetime',
    ];

    /**
     * RELATIONS
     */

    /**
     * Get all of the annualReviews for the AnnualUpdateWindow
     */
    public function annualReviews(): HasMany
    {
        return $this->hasMany(AnnualUpdate::class);
    }

    // SCOPES

    public function scopeForYear($query, $year)
    {
        return $query->where('for_year', $year);
    }

    public static function latest()
    {
        return self::query()->orderByDesc('for_year')->orderBy('created_at')->first();
    }
}
