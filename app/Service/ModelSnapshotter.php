<?php

namespace App\Service;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;

class ModelSnapshotter
{
    public function createSnapshot(Model $model): array
    {
        $snapshot = [
            'class' => get_class($model),
            'attributes' => $model->getAttributes(),
            'relations' => $this->snapshotRelations($model)
        ];

        return $snapshot;
    }

    private function snapshotRelations($model): array
    {
        return collect($model->getRelations())->map(function ($relation, $key) {
            if ($relation instanceof Collection) {
                return $relation->map(fn (Model $item) => $this->createSnapshot($item));
            }

            if (is_null($relation)) {
                return null;
            }

            return $this->createSnapshot($relation);
        })->toArray();
    }

    public function initModelFromSnapshot($snapshot): Model
    {
        $model = new ($snapshot['class'])();
        foreach ($snapshot['attributes'] as $key => $value) {
            $model->setAttribute($key, $value);
        }
        
        if (isset($snapshot['relations'])) {
            foreach ($snapshot['relations'] as $key => $relation) {
                if (!isset($relation['class'])) {
                    $model->setRelation($key, collect($relation)->map(fn($r) => $this->initModelFromSnapshot($r)));
                    continue;
                }
    
                $model->setRelation($key, $this->initModelFromSnapshot($relation));
            }
        }

        return $model;
    }

}