<?php

namespace SqlCrudGenerator\Services;

use Illuminate\Support\Str;
use SqlCrudGenerator\Services\Generators\BladeGenerator;
use SqlCrudGenerator\Services\Generators\ControllerGenerator;
use SqlCrudGenerator\Services\Generators\ModelGenerator;
use SqlCrudGenerator\Services\Generators\ApiControllerGenerator;
use SqlCrudGenerator\Services\Generators\ApiRouteGenerator;
use SqlCrudGenerator\Services\Generators\MigrationGenerator;
use SqlCrudGenerator\Services\Generators\ModuleRouteGenerator;
use SqlCrudGenerator\Services\Generators\RequestGenerator;

class ModuleCrudGeneratorService
{
    public function __construct(
        private readonly ModelGenerator $modelGenerator,
        private readonly ControllerGenerator $controllerGenerator,
        private readonly ApiControllerGenerator $apiControllerGenerator,
        private readonly RequestGenerator $requestGenerator,
        private readonly BladeGenerator $bladeGenerator,
        private readonly ModuleRouteGenerator $moduleRouteGenerator,
        private readonly ApiRouteGenerator $apiRouteGenerator,
        private readonly MigrationGenerator $migrationGenerator
    ) {
    }

    public function buildContext(string $moduleName, array $options = []): array
    {
        $normalized = Str::studly(trim($moduleName));
        $viewNamespace = Str::kebab($normalized);
        $routePrefix = Str::kebab($normalized);
        $tablePrefix = Str::snake($normalized);
        $baseModulePath = base_path(trim(config('sql-crud-generator.module_base_path', 'app/Modules'), '/\\')).DIRECTORY_SEPARATOR.$normalized;

        return [
            'module_name' => $normalized,
            'module_base_path' => $baseModulePath,
            'controllers_namespace' => 'App\\Http\\Controllers\\'.$normalized,
            'models_namespace' => 'App\\Models\\'.$normalized,
            'requests_namespace' => 'App\\Http\\Requests\\'.$normalized,
            'controllers_path' => base_path('app/Http/Controllers/'.$normalized),
            'api_controllers_path' => base_path('app/Http/Controllers/'.$normalized.'/Api'),
            'models_path' => base_path('app/Models/'.$normalized),
            'requests_path' => base_path('app/Http/Requests/'.$normalized),
            'views_path' => resource_path('views/'.$viewNamespace),
            'module_reference_path' => resource_path('views/'.$viewNamespace.'/_generator_assets'),
            'view_namespace' => $viewNamespace,
            'route_name_prefix' => $viewNamespace.'.',
            'route_url_prefix' => $routePrefix,
            'table_prefix' => $tablePrefix,
            'crud_mode' => $options['crud_mode'] ?? 'web',
            'enable_ajax' => (bool) ($options['enable_ajax'] ?? false),
            'enable_import_export' => (bool) ($options['enable_import_export'] ?? false),
            'enable_email_notifications' => (bool) ($options['enable_email_notifications'] ?? false),
            'notification_email' => trim((string) ($options['notification_email'] ?? '')),
            'template_mode' => $options['template_mode'] ?? 'default',
            'template_content' => $options['template_content'] ?? '',
            'template_source_name' => $options['template_source_name'] ?? '',
            'screenshot_source_name' => $options['screenshot_source_name'] ?? '',
            'generate_migrations' => (bool) ($options['generate_migrations'] ?? true),
            'auto_run_migrations' => (bool) ($options['auto_run_migrations'] ?? false),
        ];
    }

    public function generate(array $parsedSchema, array $context): array
    {
        $effectiveSchema = $this->applyTablePrefixing($parsedSchema, $context);
        $webControllers = $this->controllerGenerator->generate($effectiveSchema, $context);
        $apiControllers = $this->apiControllerGenerator->generate($effectiveSchema, $context);

        return [
            'parsed_schema' => $effectiveSchema,
            'models' => $this->modelGenerator->generate($effectiveSchema, $context),
            'controllers' => array_merge($webControllers, $apiControllers),
            'requests' => $this->requestGenerator->generate($effectiveSchema, $context),
            'views' => $this->bladeGenerator->generate($effectiveSchema, $context),
            'migrations' => $this->migrationGenerator->generate($effectiveSchema, $context),
            'route_snippets_web' => $this->moduleRouteGenerator->generate($effectiveSchema, $context),
            'route_snippets_api' => $this->apiRouteGenerator->generate($effectiveSchema, $context),
        ];
    }

    private function applyTablePrefixing(array $schema, array $context): array
    {
        $tables = $schema['tables'] ?? [];
        $prefix = trim((string) ($context['table_prefix'] ?? ''), '_');
        if ($prefix === '') {
            return $schema;
        }

        $map = [];
        foreach ($tables as $table) {
            $original = $table['name'];
            $mapped = str_starts_with($original, $prefix.'_') ? $original : $prefix.'_'.$original;
            $map[$original] = $mapped;
        }

        $updated = [];
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $newTable = $table;
            $newTable['name'] = $map[$tableName] ?? $tableName;

            $foreignKeys = $table['foreign_keys'] ?? [];
            $newForeignKeys = [];
            foreach ($foreignKeys as $foreignKey) {
                $refTable = $foreignKey['references_table'] ?? '';
                $foreignKey['references_table'] = $map[$refTable] ?? (str_starts_with($refTable, $prefix.'_') ? $refTable : $prefix.'_'.$refTable);
                $newForeignKeys[] = $foreignKey;
            }
            $newTable['foreign_keys'] = $newForeignKeys;
            $updated[] = $newTable;
        }

        return ['tables' => $updated];
    }
}
