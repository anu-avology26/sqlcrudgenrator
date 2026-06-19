<?php

namespace SqlCrudGenerator\Services\Generators;

use Illuminate\Support\Str;

class ApiRouteGenerator
{
    public function generate(array $schema, array $context): string
    {
        if (!in_array($context['crud_mode'], ['api', 'both'], true)) {
            return '';
        }

        $apiControllersNamespace = $context['controllers_namespace'].'\\Api';
        $routeUrlPrefix = $context['route_url_prefix'];
        $routeNamePrefix = $context['route_name_prefix'].'api.';
        $importExportEnabled = $context['enable_import_export'];
        $moduleName = $context['module_name'];

        $lines = [];
        $blockStart = "// <sql-crud-generator:{$moduleName}:api:start>";
        $blockEnd = "// <sql-crud-generator:{$moduleName}:api:end>";
        $lines[] = $blockStart;

        foreach ($schema['tables'] ?? [] as $table) {
            $modelName = Str::studly(Str::singular($table['name']));
            $controllerName = $modelName.'ApiController';
            $resourceUri = Str::snake(Str::pluralStudly($modelName));
            $controllerFqcn = '\\'.$apiControllersNamespace.'\\'.$controllerName;
            $routeUri = $routeUrlPrefix.'/'.$resourceUri;
            $routeName = $routeNamePrefix.$resourceUri;

            $lines[] = "Route::get('{$routeUri}', [{$controllerFqcn}::class, 'index'])->name('{$routeName}.index');";
            $lines[] = "Route::post('{$routeUri}', [{$controllerFqcn}::class, 'store'])->name('{$routeName}.store');";
            if ($importExportEnabled) {
                $lines[] = "Route::get('{$routeUri}/export', [{$controllerFqcn}::class, 'export'])->name('{$routeName}.export');";
                $lines[] = "Route::post('{$routeUri}/import', [{$controllerFqcn}::class, 'import'])->name('{$routeName}.import');";
            }
            $lines[] = "Route::get('{$routeUri}/{id}', [{$controllerFqcn}::class, 'show'])->name('{$routeName}.show');";
            $lines[] = "Route::put('{$routeUri}/{id}', [{$controllerFqcn}::class, 'update'])->name('{$routeName}.update');";
            $lines[] = "Route::delete('{$routeUri}/{id}', [{$controllerFqcn}::class, 'destroy'])->name('{$routeName}.destroy');";
            $lines[] = '';
        }

        $lines[] = $blockEnd;

        return trim(implode("\n", $lines));
    }
}
