<?php

namespace App\Modules\ExpertPanel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Modules\Funding\Models\FundingSource;
use App\Modules\Person\Models\Person;
use App\Models\Traits\HasUuid;
use Illuminate\Support\Str;

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
        'nih_reporter_url',
        'nih_ic',
        'rep_contacts',
        'notes',
    ];

    protected $casts = [
        'start_date'   => 'date:Y-m-d',
        'end_date'     => 'date:Y-m-d',
        'rep_contacts' => 'array', 
    ];

    protected $appends = [
        'contact_1_role',
        'contact_1_name',
        'contact_1_email',
        'contact_1_phone',
        'contact_2_role',
        'contact_2_name',
        'contact_2_email',
        'contact_2_phone',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }


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

     public function setRepContactsAttribute($value): void
    {
        $normalized = $this->normalizeRepContacts($value);
        $this->attributes['rep_contacts'] = empty($normalized) ? null : json_encode($normalized);
    }

    public function getRepContactsAttribute($value): array
    {
        if (is_array($value)) {
            return $this->normalizeRepContacts($value);
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return $this->normalizeRepContacts(is_array($decoded) ? $decoded : []);
        }
        return [];
    }

    private function normalizeRepContacts($value): array
    {
        if (!is_array($value)) { $value = []; }

        $contact1 = $value[0] ?? [];
        $contact2 = $value[1] ?? [];

        $out = [
            [
                'role'  => $this->cleanText($contact1['role']  ?? null),
                'name'  => $this->cleanText($contact1['name']  ?? null),
                'email' => $this->cleanText($contact1['email'] ?? null),
                'phone' => $this->cleanText($contact1['phone'] ?? null),
            ],
            [
                'role'  => $this->cleanText($contact2['role']  ?? null),
                'name'  => $this->cleanText($contact2['name']  ?? null),
                'email' => $this->cleanText($contact2['email'] ?? null),
                'phone' => $this->cleanText($contact2['phone'] ?? null),
            ],
        ];

        $hasAny = false;
        foreach ($out as $contact) {
            if ($contact['role'] || $contact['name'] || $contact['email'] || $contact['phone']) {
                $hasAny = true;
                break;
            }
        }
        return $hasAny ? $out : [];
    }

    private function cleanText($value, int $max = 255): ?string
    {
        if ($value === null) return null;
        $str = trim(strip_tags((string) $value));
        if ($str === '') return null;
        return mb_strlen($str) > $max ? mb_substr($str, 0, $max) : $str;
    }

    private function repContactField(int $idx, string $key): ?string
    {
        $rc = $this->rep_contacts ?? [];
        return $rc[$idx][$key] ?? null;
    }
    
    public function getContact1RoleAttribute()  { return $this->repContactField(0, 'role'); }
    public function getContact1NameAttribute()  { return $this->repContactField(0, 'name'); }
    public function getContact1EmailAttribute() { return $this->repContactField(0, 'email'); }
    public function getContact1PhoneAttribute() { return $this->repContactField(0, 'phone'); }

    public function getContact2RoleAttribute()  { return $this->repContactField(1, 'role'); }
    public function getContact2NameAttribute()  { return $this->repContactField(1, 'name'); }
    public function getContact2EmailAttribute() { return $this->repContactField(1, 'email'); }
    public function getContact2PhoneAttribute() { return $this->repContactField(1, 'phone'); }
    
}
