<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\Funding\Models\FundingSource;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Person\Models\Person;

class FundingAward extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'expert_panel_id',
        'funding_source_id',
        'start_date',
        'end_date',
        'award_number',
        'nih_reporter_url',
        'nih_ic',
        'contact_1_role',
        'contact_1_name',
        'contact_1_email',
        'contact_1_phone',
        'contact_2_role',
        'contact_2_name',
        'contact_2_email',
        'contact_2_phone',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
    ];

    public function expertPanel()
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    public function fundingSource()
    {
        return $this->belongsTo(FundingSource::class);
    }

    public function contactPis()
    {
        return $this->belongsToMany(Person::class, 'funding_award_contact_pis')
            ->withPivot(['is_primary'])
            ->withTimestamps();
    }
}
