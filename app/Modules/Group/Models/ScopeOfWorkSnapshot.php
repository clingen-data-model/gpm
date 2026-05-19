<?php

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScopeOfWorkSnapshot extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'scope_of_work_version_id',
        'snapshot_schema_version',
        'snapshot',
    ];

    protected $casts = [
        'id' => 'integer',
        'scope_of_work_version_id' => 'integer',
        'snapshot' => 'array',
    ];

    public function version()
    {
        return $this->belongsTo(ScopeOfWorkVersion::class, 'scope_of_work_version_id');
    }
}