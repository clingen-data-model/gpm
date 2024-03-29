<?php

namespace App\Modules\ExpertPanel\Models;

use Database\Factories\RulesetFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Modules\ExpertPanel\Models\RulesetStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $cspec_ruleset_id
 * @property string $specification_id
 * @property int $status_id
 * @property \Carbon\Carbon $deleted_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Ruleset extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'specification_rulesets';

    protected $primaryKey = 'cspec_ruleset_id';
    public $incrementing = false;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'cspec_ruleset_id',
        'specification_id',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'status_id' => 'integer',
    ];


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function specification()
    {
        return $this->belongsTo(Specification::class, 'specification_id', 'cspec_id');
    }

    protected static function newFactory()
    {
        return new RulesetFactory();
    }
}
