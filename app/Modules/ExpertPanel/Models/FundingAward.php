<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Funding\Models\FundingSource;
use App\Modules\Person\Models\Person;
use App\Models\Traits\HasUuid;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FundingAward extends Model
{
    use SoftDeletes;
    use HasUuid;

    protected $fillable = [
        'expert_panel_id',
        'funding_source_id',
        'start_date',
        'end_date',
        'award_number',
        'award_url',
        'funding_source_division',
        'rep_contacts',
        'notes',
    ];

    protected $casts = [
        'start_date'    => 'date:Y-m-d',
        'end_date'      => 'date:Y-m-d',
        'rep_contacts'  => 'array',
    ];

    public function expertPanel(): BelongsTo
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    public function fundingSource(): BelongsTo
    {
        return $this->belongsTo(FundingSource::class);
    }

    public function contactPis(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'funding_award_contact_pis')
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }
}