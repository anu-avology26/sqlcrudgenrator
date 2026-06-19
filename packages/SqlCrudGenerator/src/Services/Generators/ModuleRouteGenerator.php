<?php

namespace SqlCrudGenerator\Services\Generators;

use Illuminate\Support\Str;

class ModuleRouteGenerator
{
    public function generate(array $schema, array $context): string
    {
        if (!in_array($context['crud_mode'], ['web', 'both'], true)) {
            return '';
        }

        $controllersNamespace = $context['controllers_namespace'];
        $routeUrlPrefix = $context['route_url_prefix'];
        $routeNamePrefix = $context['route_name_prefix'];
        $importExportEnabled = $context['enable_import_export'];
        $moduleName = $context['module_name'];

        $lines = [];
        $blockStart = "// <sql-crud-generator:{$moduleName}:web:start>";
        $blockEnd = "// <sql-crud-generator:{$moduleName}:web:end>";
        $lines[] = $blockStart;

        foreach ($schema['tables'] ?? [] as $table) {
            $modelName = Str::studly(Str::singular($table['name']));
            $controllerName = $modelName.'Controller';
            $resourceUri = Str::snake(Str::pluralStudly($modelName));
            $controllerFqcn = '\\'.$controllersNamespace.'\\'.$controllerName;
            $routeUri = $routeUrlPrefix.'/'.$resourceUri;
            $routeName = $routeNamePrefix.$resourceUri;

            $lines[] = "Route::get('{$routeUri}', [{$controllerFqcn}::class, 'index'])->name('{$routeName}.index');";
            $lines[] = "Route::get('{$routeUri}/create', [{$controllerFqcn}::class, 'create'])->name('{$routeName}.create');";
            $lines[] = "Route::post('{$routeUri}', [{$controllerFqcn}::class, 'store'])->name('{$routeName}.store');";
            if ($importExportEnabled) {
                $lines[] = "Route::get('{$routeUri}/export', [{$controllerFqcn}::class, 'export'])->name('{$routeName}.export');";
                $lines[] = "Route::post('{$routeUri}/import', [{$controllerFqcn}::class, 'import'])->name('{$routeName}.import');";
            }
            $lines[] = "Route::get('{$routeUri}/{id}/edit', [{$controllerFqcn}::class, 'edit'])->name('{$routeName}.edit');";
            $lines[] = "Route::put('{$routeUri}/{id}', [{$controllerFqcn}::class, 'update'])->name('{$routeName}.update');";
            $lines[] = "Route::delete('{$routeUri}/{id}', [{$controllerFqcn}::class, 'destroy'])->name('{$routeName}.destroy');";
            $lines[] = "Route::get('{$routeUri}/{id}', [{$controllerFqcn}::class, 'show'])->name('{$routeName}.show');";
            $lines[] = '';
        }

        $lines[] = $blockEnd;

        return trim(implode("\n", $lines));
    }
}
