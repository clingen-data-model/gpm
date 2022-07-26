<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Illuminate\Support\Carbon;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use App\Http\Controllers\DocumentController;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'owner_id',
        'owner_type',
        'metadata',
        'document_type_id',
        'notes',
        'is_final',
        'step',
        'version',
        'date_received'
    ];

    protected $casts = [
        'owner_id' => 'integer',
        'document_type_id' => 'integer',
        'metadata' => 'array',
    ];

    protected $dates = [
        'date_received'
    ];

    protected $appends = [
        'download_url',
        'version',
        'step',
        'is_final',
        'date_received'
    ];

    # Relationships

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function type(): BelongsTo
    {
        return $this->documentType();
    }

    public function owner()
    {
        return $this->morphTo();
    }
    

    /**
     * SCOPES
     */
    public function scopeType($query, $type)
    {
        $id = $type;
        if ($type instanceof DocumentType) {
            $id = $type->id;
        }
        return $query->where('document_type_id', $id);
    }

    public function scopeIsVersion($query, $version)
    {
        return $query->where('metadata->version', $version);
    }

    public function scopeFinal($query)
    {
        return $query->where('metadata->is_final', 1);
    }
    

    /**
     * ACESSORS & MUTATORS
     */
    public function getStepAttribute(): ?int
    {
        return isset($this->metadata['step']) ? $this->metadata['step'] : null;
    }
    
    public function setStepAttribute(int $step): void
    {
        $md = $this->metadata;
        $md['step'] = $step;

        $this->metadata = $md;
    }
    
    public function getVersionAttribute(): ?int
    {
        return isset($this->metadata['version']) ? $this->metadata['version'] : null;
    }
    
    public function setVersionAttribute(int $version): void
    {
        $md = $this->metadata;
        $md['version'] = $version;

        $this->metadata = $md;
    }
    
    public function getDateReceivedAttribute(): ?Carbon
    {
        return isset($this->metadata['date_received']) ? Carbon::parse($this->metadata['date_received']) : null;
    }
    
    public function setDateReceivedAttribute($dateReceived): void
    {
        $dateString = $dateReceived;
        if (is_object($dateString)) {
            $dateString = $dateReceived->format('Y-m-d H:i:s');
        }
        $md = $this->metadata;
        $md['date_received'] = $dateReceived;

        $this->metadata = $md;
    }

    public function getIsFinalAttribute()
    {
        return isset($this->metadata['is_final']) ? $this->metadata['is_final'] : null;
    }

    public function setIsFinalAttribute($value)
    {
        $md = $this->metadata;

        $md['is_final'] = $value;
        $this->metadata = $md;
    }

    /**
     * DOMAIN
     */
    
    public function getDownloadUrlAttribute()
    {
        return url()->action([DocumentController::class, 'show'], [$this->uuid]);
    }

    public function belongsToUser(User $user): bool
    {
        if ($this->owner_type == get_class($user) && $this->owner_id == $user->id) {
            return true;
        }

        if (
            $user->person
            && $this->owner_type == get_class($user->person)
            && $this->owner_id == $user->person->id
        ) {
            return true;
        }

        return false;
    }
}
