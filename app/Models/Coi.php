<?php

namespace App\Models;

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
        $coiDef = static::getDefinition();

        $responseData = collect($this->data)->map(function ($value, $key) use ($coiDef) {
            $questions = collect($coiDef->questions)->keyBy('name')->toArray();
            return [
                'question' => $questions[$key]->question,
                'response' => $value
            ];
        })->toArray();

        $responseData['application_id'] = $this->application_id;
        
        return $responseData;
    }

    static public function getDefinition()
    {
        return Cache::remember('coi-definition', 360, function () {
            return json_decode(file_get_contents(base_path('resources/surveys/coi.json')));
        });
    }
    
}
