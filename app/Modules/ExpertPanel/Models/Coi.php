<?php

namespace App\Modules\ExpertPanel\Models;

use Database\Factories\CoiFactory;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Group\Models\GroupMember;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coi extends Model
{
    use HasFactory;

    private $cachedResponseDocument = null;

    public $fillable = [
        'uuid',
        'group_member_id',
        'group_id',
        'completed_at',
        'data',
        'version'
    ];

    public $casts = [
        'data' => 'object',
        'group_member_id' => 'integer',
        'group_id' => 'integer',
        'completed_at' => 'datetime'
    ];

    public $appends = [
        'response_document'
    ];

    static public function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            if (!$model->version) {
                $model->version = config('coi.current_version');
            }
        });
    }


    /**
     * RELATIONS
     */
    public function groupMember(): BelongsTo
    {
        return $this->belongsTo(GroupMember::class);
    }

    public function expertPanel()
    {
        return $this->belongsTo(ExpertPanel::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }


    /**
     * SCOPES
     */
    public function scopeForApplication($query, $expertPanel)
    {
        $id = $expertPanel;
        if ($expertPanel instanceof ExpertPanel) {
            $id = $expertPanel->id;
        }
        return $query->where('group_id', $id);
    }

    // ACCESSORS
    public function getResponseDocumentAttribute()
    {
        // Cache on the model instance to avoid recomputing
        if ($this->cachedResponseDocument !== null) {
            return $this->cachedResponseDocument;
        }

        $coiDef = isset($this->data->document_uuid)
                    ? static::getLegacyDefinition()
                    : static::getDefinition();

        // Pre-compute the questions collection once instead of in the closure
        $questions = collect($coiDef->questions)->keyBy('name');

        $responseData = collect($this->data)
            ->map(function ($value, $key) use ($questions) {
                if (!isset($questions[$key])) {
                    return null;
                }
                return [
                    'question' => $questions->get($key)->question,
                    'response' => $value
                ];
            })
            ->filter()
            ->toArray();

        $responseData['group_id'] = $this->expertPanel_id;

        // Cache the result on this instance (in-memory only)
        $this->cachedResponseDocument = $responseData;

        return $this->cachedResponseDocument;
    }

    public function getResponseForHumansAttribute()
    {
        $data = (array)$this->data;
        $questions = collect($this->getDefinition()->questions)->keyBy('name');

        $humanReadable = [];

        foreach ($questions as $name => $def) {
            $response = $data[$name];
            $readableResponse = $response;
            if (in_array($def->type, ['multiple-choice'])) {
                $options = collect($def->options)->pluck('label', 'value');
                $readableResponse = $options[$response];
            }
            if ($def->type == 'yes-no') {
                $readableResponse = $response == 1 ? 'Yes' : 'No';
            }
            $humanReadable[$name] = $readableResponse;
        }

        return $humanReadable;
    }

    // DOMAIN

    public function getDefinition()
    {
        return Cache::remember('coi-definition-'.$this->version, 360, function () {
            $defPath = config('coi.definitions')[$this->version];
            return json_decode(file_get_contents(base_path($defPath)));
        });
    }

    public static function getLegacyDefinition()
    {
        return Cache::remember('legacy-coi-definition', 360, function () {
            return json_decode(file_get_contents(base_path('resources/surveys/legacy_coi.json')));
        });
    }


    // Factory
    protected static function newFactory()
    {
        return new CoiFactory();
    }
}
