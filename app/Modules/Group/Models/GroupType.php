<?php

namespace App\Modules\Group\Models;

use App\Modules\Group\Enums\CurationProduct;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\GroupTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class GroupType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'fullname',
        'display_name',
        'can_be_parent',
        'curation_product',
        'is_somatic_cancer',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'can_be_parent' => 'boolean',
        'is_somatic_cancer' => 'boolean',
        'curation_product' => CurationProduct::class,
    ];

    protected $hidden = ['created_at', 'updated_at'];

    protected static function newFactory()
    {
        return new GroupTypeFactory();
    }
}
