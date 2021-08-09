<?php

namespace App\Models;

use App\Models\HasUuid;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\DocumentController;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;

class Document extends Model
{
    use HasFactory;
    use SoftDeletes;
    use HasTimestamps;
    use HasUuid;

    protected $fillable = [
        'uuid',
        'filename',
        'storage_path',
        'step',
        'metadata',
        'version',
        'date_received',
        'application_id',
        'document_type_id',
        'is_final',
        'notes',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $dates = [
        'date_received'
    ];

    protected $appends = [
        'download_url',
    ];

    # Relationships
    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function scopeType($query, $type)
    {
        $id = $type;
        if ($type instanceof DocumentType) {
            $id = $type->id;
        }
        return $query->where('document_type_id', $id);
    }

    public function scopeVersion($query, $version)
    {
        return $query->where('version', $version);
    }

    public function scopeFinal($query)
    {
        return $query->where('is_final', 1);
    }
    
    public function getDownloadUrlAttribute()
    {
        return url()->action([DocumentController::class, 'show'], [$this->uuid]);
    }
}
