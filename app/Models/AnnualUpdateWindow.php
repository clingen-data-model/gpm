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
}
