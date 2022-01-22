<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AnnualReviewWindow extends Model
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
        'start' => 'date',
        'end' => 'date'
    ];

    /**
     * RELATIONS
     */

    /**
     * Get all of the annualReviews for the AnnualReviewWindow
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function annualReviews(): HasMany
    {
        return $this->hasMany(AnnualReview::class);
    }
}
