<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class ModelSearchService
{
    public function __construct(
        private string $modelClass,
        private array $defaultWith = [],
        private ?array $defaultSelect = null,
        private $sortFunction = null,
        private $eagerLoadFunction = null,
        private $whereFunction = null,
    ) {
        //code
    }
    
    public function search($params): Collection
    {
        return $this->buildQuery($params)
                ->get();
    }
    

    public function buildQuery($params): Builder
    {
        $query = $this->modelClass::query()
                    ->with($this->defaultWith);


        $query->select($this->getSelect());

        if (isset($params['sort'])) {
            $query = $this->sortQuery($query, $params['sort']);
        }

        if (isset($params['with'])) {
            $query = $this->eagerLoadRelations($query, $params['with']);
        }

        if (isset($params['without'])) {
            $query = $this->removeEagerLoadRelations($query, $params['without']);
        }

        if (isset($params['where'])) {
            $query = $this->addWhereClause($query, $params['where']);
        }

        if (isset($params['showDeleted'])) {
            $query->withTrashed();
        }

        return $query;
    }

    private function getSelect()
    {
        if (!is_null($this->defaultSelect)) {
            return $this->defaultSelect;
        }
        $dummy = new $this->modelClass();
        return $dummy->getTable().'.*';
    }
    

    private function addWhereClause($query, $where)
    {
        if (!is_null($this->whereFunction)) {
            return ($this->whereFunction)($query, $where);
        }

        foreach ($where as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        return $query;
    }
    

    private function eagerLoadRelations($query, $with)
    {
        $relations = $with;

        if (!is_null($this->eagerLoadFunction)) {
            return ($this->eagerLoadFunction)($query, $with);
        }

        if (is_string($relations)) {
            $relations = array_map(function ($i) use ($with) {
                return trim($i);
            }, explode(',', $relations));
        }

        if ($with) {
            $query->with($relations);
        }
        
        return $query;
    }
   
    private function removeEagerLoadRelations($query, $without)
    {
        $query->without($without);
        
        return $query;
    }
    


    private function sortQuery($query, $sort)
    {
        $field = $sort['field'];
        $dir = $sort['dir'] ?? 'asc';

        if (!is_null($this->sortFunction)) {
            return ($this->sortFunction)($query, $field, $dir);
        }

        return $query->orderBy($field, $dir);
    }
}
