<?php

namespace App\Models;

use App\Modules\Application\Models\Application;
use Hamcrest\Type\IsObject;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coi extends Model
{
    use HasFactory;

    public $fillable = [
        'application_id',
        'email',
        'data'
    ];

    public $casts = [
        'data' => 'object'
    ];
    
    public $appends = [
        'response_document'
    ];
    
    public function getResponseDocumentAttribute()
    {
        $coiDef = isset($this->data->document_uuid)
                    ? static::getLegacyDefinition()
                    : static::getDefinition();

        $responseData = collect($this->data)
            ->map(function ($value, $key) use ($coiDef) {
                $questions = collect($coiDef->questions)->keyBy('name');
                if (!isset($questions[$key])) {
                    Log::debug('key, questions->keys(): ', ['keys'=> $key, 'questions'=>$questions->keys()->toArray()]);
                    return;
                }
                return [
                    'question' => $questions->get($key)->question,
                    'response' => $value
                ];
            })
            ->filter()
            ->toArray();

        $responseData['application_id'] = $this->application_id;
        
        return $responseData;
    }

    public function getResponseForHumans()
    {
        $data = (array)$this->data;
        $questions = collect(static::getDefinition()->questions)->keyBy('name');

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
        // foreach ($data as $key => $response) {
        //     $question = $questions->get($key);
        //     $readableResponse = $value;
        //     if (in_array($question->type, ['multiple-choice'])) {
        //         $options = collect($question->options)->pluck('label', 'value');
        //         $readableResponse = $options[$value];
        //     }
        //     if ($question->type == 'yes-no') {
        //         $readableResponse = $value == 1 ? 'Yes' : 'No';
        //     }
        //     $humanReadable[$key] = $readableResponse;
        // }
        return $humanReadable;
    }
    

    /**
     * SCOPES
     */
    public function scopeForApplication($query, $application)
    {
        $id = $application;
        if ($application instanceof Application) {
            $id = $application->id;
        }
        return $query->where('application_id', $id);
    }
    

    static public function getDefinition()
    {
        return Cache::remember('coi-definition', 360, function () {
            return json_decode(file_get_contents(base_path('resources/surveys/coi.json')));
        });
    }

    static public function getLegacyDefinition()
    {
        return Cache::remember('legacy-coi-definition', 360, function () {
            return json_decode(file_get_contents(base_path('resources/surveys/legacy_coi.json')));
        });
    }
    
}
