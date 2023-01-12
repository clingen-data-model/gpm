<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnnualUpdateWindow extends Model
{
    use HasFactory;

    protected $fillable = [
        'for_year',
        'start',
        'end'
    ];

    protected $casts = [
        'id' => 'int',
        'for_year' => 'int',
        'start' => 'datetime',
        'end' => 'datetime'
    ];

    /**
     * RELATIONS
     */

    /**
     * Get all of the annualReviews for the AnnualUpdateWindow
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
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

    static public function latest()
    {
        return self::query()->order_by('for_year', 'desc')->order_by('created_at')->first();
    }


}
