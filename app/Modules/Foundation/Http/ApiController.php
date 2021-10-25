<?php
namespace App\Modules\Foundation\Http;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Modules\Foundation\Http\DefaultResource;

abstract class ApiController
{
    abstract protected function getModelNamespace(): string;
    abstract protected function getResourceNamespace(): string;
    abstract protected function getHiddenModels(): array;
    

    public function index(Request $request, $classString)
    {
        $modelClass = $this->resolveModelClass($classString);

        return $this->resolveEloquentResource($classString)::collection($modelClass::all());
    }

    public function show(Request $request, $classString, $id)
    {
        $modelClass = $this->resolveModelClass($classString);
        $model = $modelClass::find($id);

        $resourceClass = $this->resolveEloquentResource($classString);

        return new $resourceClass($model);
    }

    private function resolveModelClass($classString)
    {
        $className = $this->resolveClassName($classString);
        if (!class_exists($className)) {
            abort(404, 'We couldn\'t find what you were looking for');
        }

        return $className;
    }

    private function resolveEloquentResource($classString)
    {
        $className = $this->getResourceNamespace().substr($this->resolveClassName($classString), 5).'Resource';

        if (!class_exists($className)) {
            return DefaultResource::class;
        }

        return $className;
    }

    private function resolveClassName($classString)
    {
        $className = $this->getModelNamespace().ucfirst(Str::camel(Str::singular($classString)));

        if (!class_exists($className)) {
            abort(404, 'We couldn\'t find what you were looking for.');
        }

        if ($this->modelHiddenFromApi($className)) {
            abort(404, 'We couldn\'t find what you were looking for.');
        }

        return $className;
    }

    private function modelHiddenFromApi($className)
    {
        return in_array($className, $this->getHiddenModels());
        return false;
    }
}
