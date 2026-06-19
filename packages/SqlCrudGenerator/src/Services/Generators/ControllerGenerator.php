<?php

namespace SqlCrudGenerator\Services\Generators;

use Illuminate\Support\Str;

class ControllerGenerator
{
    public function generate(array $schema, array $context): array
    {
        if (!in_array($context['crud_mode'], ['web', 'both'], true)) {
            return [];
        }

        $controllers = [];
        $controllersNamespace = $context['controllers_namespace'];
        $routePrefix = $context['route_name_prefix'];
        $viewBasePath = $context['view_namespace'];
        $notificationsEnabled = $context['enable_email_notifications'] ? 'true' : 'false';
        $notificationEmail = addslashes($context['notification_email'] ?? '');
        $importExportEnabled = $context['enable_import_export'] ? 'true' : 'false';
        $ajaxEnabled = $context['enable_ajax'] ? 'true' : 'false';
        $tables = $schema['tables'] ?? [];

        foreach ($tables as $table) {
            $modelName = $this->modelName($table['name']);
            $controllerName = $modelName.'Controller';
            $storeRequest = 'Store'.$modelName.'Request';
            $updateRequest = 'Update'.$modelName.'Request';
            $viewBase = Str::snake(Str::pluralStudly($modelName));
            $resourceVariable = Str::camel($modelName);
            $resourceCollection = Str::camel(Str::pluralStudly($modelName));
            $modelNamespace = $context['models_namespace'].'\\'.$modelName;
            $requestNamespace = $context['requests_namespace'].'\\';
            $fillable = $this->buildFillable($table);
            $searchableColumns = $this->buildSearchableColumns($table, $fillable);
            $sortableColumns = $this->buildSortableColumns($table);
            $belongsToMeta = $this->buildBelongsToMeta($table, $tables);
            $relatedImportLines = [];
            foreach ($belongsToMeta as $relation) {
                $relatedImportLines[] = 'use '.$context['models_namespace'].'\\'.$relation['related_model'].';';
            }
            $relatedImportBlock = implode("\n", array_values(array_unique($relatedImportLines)));
            $fillableArrayCode = empty($fillable) ? '[]' : "['".implode("', '", $fillable)."']";
            $searchableArrayCode = empty($searchableColumns) ? '[]' : "['".implode("', '", $searchableColumns)."']";
            $sortableArrayCode = empty($sortableColumns) ? '[]' : "['".implode("', '", $sortableColumns)."']";
            $createViewDataBlock = $this->buildCreateViewDataBlock($belongsToMeta, $viewBasePath, $viewBase);
            $editViewDataBlock = $this->buildEditViewDataBlock($belongsToMeta, $resourceVariable, $viewBasePath, $viewBase);
            $withRelationsCode = $this->buildWithRelationsCode($belongsToMeta);
            $withRelationsArrayCode = $this->withRelationsArrayCode($belongsToMeta);

            $exportImportMethods = str_replace(
                ['{MODEL}', '{MODEL_PLURAL}', '{FILLABLE}', '{IMPORT_EXPORT_ENABLED}', '{INDEX_ROUTE}'],
                [$modelName, Str::pluralStudly($modelName), $fillableArrayCode, $importExportEnabled, $routePrefix.$viewBase],
                $this->exportImportMethods()
            );

            $controllers[$controllerName.'.php'] = <<<PHP
<?php

namespace {$controllersNamespace};

use App\Http\Controllers\Controller;
use {$requestNamespace}{$storeRequest};
use {$requestNamespace}{$updateRequest};
use {$modelNamespace};
{$relatedImportBlock}
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class {$controllerName} extends Controller
{
    public function index(Request \$request): View|JsonResponse
    {
        \$query = {$modelName}::query();
{$withRelationsCode}
        \$search = trim((string) \$request->query('search', ''));
        \$sort = (string) \$request->query('sort', 'created_at');
        \$direction = strtolower((string) \$request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        \$searchableColumns = {$searchableArrayCode};
        \$sortableColumns = {$sortableArrayCode};

        if (\$search !== '' && !empty(\$searchableColumns)) {
            \$query->where(static function (\$builder) use (\$search, \$searchableColumns): void {
                foreach (\$searchableColumns as \$index => \$column) {
                    if (\$index === 0) {
                        \$builder->where(\$column, 'like', '%'.\$search.'%');
                    } else {
                        \$builder->orWhere(\$column, 'like', '%'.\$search.'%');
                    }
                }
            });
        }

        if (!in_array(\$sort, \$sortableColumns, true)) {
            \$sort = 'created_at';
        }

        \${$resourceCollection} = \$query
            ->orderBy(\$sort, \$direction)
            ->paginate(15)
            ->withQueryString();

        if ({$ajaxEnabled} === true && \$request->expectsJson()) {
            return response()->json(\${$resourceCollection});
        }

        return view('{$viewBasePath}.{$viewBase}.index', compact('{$resourceCollection}'));
    }

    public function create(): View
    {
{$createViewDataBlock}
    }

    public function store({$storeRequest} \$request): RedirectResponse|JsonResponse
    {
        \${$resourceVariable} = {$modelName}::query()->create(\$request->validated());
        \$this->sendCrudNotification('created', \${$resourceVariable});

        if ({$ajaxEnabled} === true && \$request->expectsJson()) {
            return response()->json([
                'message' => '{$modelName} created successfully.',
                'data' => \${$resourceVariable},
            ], 201);
        }

        return redirect()->route('{$routePrefix}{$viewBase}.index')
            ->with('success', '{$modelName} created successfully.');
    }

    public function show(int|string \$id): View
    {
        \${$resourceVariable} = {$modelName}::query()->with({$withRelationsArrayCode})->findOrFail(\$id);

        return view('{$viewBasePath}.{$viewBase}.show', compact('{$resourceVariable}'));
    }

    public function edit(int|string \$id): View
    {
        \${$resourceVariable} = {$modelName}::query()->with({$withRelationsArrayCode})->findOrFail(\$id);

{$editViewDataBlock}
    }

    public function update({$updateRequest} \$request, int|string \$id): RedirectResponse|JsonResponse
    {
        \${$resourceVariable} = {$modelName}::query()->findOrFail(\$id);
        \${$resourceVariable}->update(\$request->validated());
        \$this->sendCrudNotification('updated', \${$resourceVariable});

        if ({$ajaxEnabled} === true && \$request->expectsJson()) {
            return response()->json([
                'message' => '{$modelName} updated successfully.',
                'data' => \${$resourceVariable},
            ]);
        }

        return redirect()->route('{$routePrefix}{$viewBase}.index')
            ->with('success', '{$modelName} updated successfully.');
    }

    public function destroy(Request \$request, int|string \$id): RedirectResponse|JsonResponse
    {
        \${$resourceVariable} = {$modelName}::query()->findOrFail(\$id);
        \${$resourceVariable}->delete();

        if ({$ajaxEnabled} === true && \$request->expectsJson()) {
            return response()->json(['message' => '{$modelName} deleted successfully.']);
        }

        return redirect()->route('{$routePrefix}{$viewBase}.index')
            ->with('success', '{$modelName} deleted successfully.');
    }
{$exportImportMethods}
    private function sendCrudNotification(string \$action, {$modelName} \$entity): void
    {
        if ({$notificationsEnabled} !== true) {
            return;
        }

        \$to = '{$notificationEmail}' !== '' ? '{$notificationEmail}' : config('mail.from.address');
        if (empty(\$to)) {
            return;
        }

        \$subject = '{$modelName} ' . ucfirst(\$action);
        \$body = '{$modelName} has been ' . \$action . '. ID: ' . \$entity->getKey();

        try {
            Mail::raw(\$body, static function (\$message) use (\$to, \$subject): void {
                \$message->to(\$to)->subject(\$subject);
            });
        } catch (Throwable \$e) {
            // Prevent CRUD failure when mail transport is not configured.
        }
    }
}
PHP;
        }

        return $controllers;
    }

    private function exportImportMethods(): string
    {
        return <<<'PHP'

    public function export(): StreamedResponse
    {
        if ({IMPORT_EXPORT_ENABLED} !== true) {
            return response()->streamDownload(static function (): void {
                echo 'Import/export is disabled.';
            }, 'disabled.csv', ['Content-Type' => 'text/csv']);
        }

        $records = {MODEL}::query()->latest()->get();
        $fillable = {FILLABLE};

        return response()->streamDownload(static function () use ($records, $fillable): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, $fillable);
            foreach ($records as $row) {
                $line = [];
                foreach ($fillable as $column) {
                    $line[] = $row->{$column};
                }
                fputcsv($handle, $line);
            }
            fclose($handle);
        }, strtolower('{MODEL_PLURAL}').'_export.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): RedirectResponse|JsonResponse
    {
        if ({IMPORT_EXPORT_ENABLED} !== true) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Import/export is disabled.'], 422);
            }
            return back()->with('success', 'Import/export is disabled.');
        }

        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt']]);
        $fillable = {FILLABLE};
        $path = $request->file('csv_file')->getRealPath();

        if ($path === false) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unable to read uploaded CSV file.'], 422);
            }
            return back()->with('success', 'Unable to read uploaded CSV file.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unable to open uploaded CSV file.'], 422);
            }
            return back()->with('success', 'Unable to open uploaded CSV file.');
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'CSV header is missing.'], 422);
            }
            return back()->with('success', 'CSV header is missing.');
        }

        $created = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $payload = [];
            foreach ($fillable as $column) {
                if (!in_array($column, $header, true)) {
                    continue;
                }
                $sourceIndex = array_search($column, $header, true);
                if ($sourceIndex === false) {
                    continue;
                }
                $payload[$column] = $row[$sourceIndex] ?? null;
            }

            if (!empty($payload)) {
                {MODEL}::query()->create($payload);
                $created++;
            }
        }
        fclose($handle);

        if ($request->expectsJson()) {
            return response()->json(['message' => '{MODEL} import completed.', 'created_count' => $created]);
        }

        return redirect()->route('{INDEX_ROUTE}.index')
            ->with('success', '{MODEL} import completed. Created: '.$created);
    }
PHP;
    }

    private function buildBelongsToMeta(array $table, array $allTables): array
    {
        $relations = [];
        $seenColumns = [];
        foreach ($table['foreign_keys'] ?? [] as $foreignKey) {
            $column = (string) ($foreignKey['column'] ?? '');
            $ownerKey = (string) ($foreignKey['references_column'] ?? 'id');
            $referencesTable = (string) ($foreignKey['references_table'] ?? '');
            if ($column === '' || $referencesTable === '') {
                continue;
            }

            $base = preg_replace('/_id$/', '', $column) ?: $column;
            $optionsVariable = \Illuminate\Support\Str::camel($base).'Options';

            $relations[] = [
                'column' => $column,
                'related_model' => $this->modelName($referencesTable),
                'owner_key' => $ownerKey,
                'options_variable' => $optionsVariable,
                'relation_method' => Str::camel(Str::singular($base)),
                'display_column' => $this->resolveDisplayColumn($referencesTable, $allTables),
            ];
            $seenColumns[] = $column;
        }

        // Fallback: infer relation for *_id even when SQL FK constraint is not provided.
        foreach ($table['columns'] ?? [] as $columnMeta) {
            $column = (string) ($columnMeta['name'] ?? '');
            if ($column === '' || !Str::endsWith($column, '_id') || in_array($column, $seenColumns, true)) {
                continue;
            }

            $resolvedTable = $this->resolveReferenceTableName($column, (string) ($table['name'] ?? ''), $allTables);
            if ($resolvedTable === null) {
                continue;
            }

            $base = preg_replace('/_id$/', '', $column) ?: $column;
            $optionsVariable = Str::camel($base).'Options';
            $relations[] = [
                'column' => $column,
                'related_model' => $this->modelName($resolvedTable),
                'owner_key' => 'id',
                'options_variable' => $optionsVariable,
                'relation_method' => Str::camel(Str::singular($base)),
                'display_column' => $this->resolveDisplayColumn($resolvedTable, $allTables),
            ];
        }

        return $relations;
    }

    private function resolveReferenceTableName(string $column, string $currentTable, array $allTables): ?string
    {
        $base = preg_replace('/_id$/', '', $column) ?: $column;
        $pluralBase = Str::snake(Str::pluralStudly(Str::studly($base)));
        $singularBase = Str::snake(Str::singular($base));

        $tableNames = array_values(array_map(
            static fn (array $table): string => (string) ($table['name'] ?? ''),
            $allTables
        ));

        $prefix = '';
        if (str_contains($currentTable, '_')) {
            $prefix = explode('_', $currentTable, 2)[0];
        }

        $candidates = array_filter([
            $pluralBase,
            $singularBase,
            $prefix !== '' ? $prefix.'_'.$pluralBase : null,
            $prefix !== '' ? $prefix.'_'.$singularBase : null,
        ]);

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $tableNames, true)) {
                return $candidate;
            }
        }

        foreach ($tableNames as $tableName) {
            if (Str::endsWith($tableName, '_'.$pluralBase) || Str::endsWith($tableName, '_'.$singularBase)) {
                return $tableName;
            }
        }

        return null;
    }

    private function buildCreateViewDataBlock(array $belongsToMeta, string $viewBasePath, string $viewBase): string
    {
        if (empty($belongsToMeta)) {
            return "        return view('{$viewBasePath}.{$viewBase}.create');";
        }

        $loads = [];
        $compact = [];
        foreach ($belongsToMeta as $relation) {
            $orderBy = $relation['display_column'] ?? $relation['owner_key'];
            $loads[] = "        \${$relation['options_variable']} = {$relation['related_model']}::query()->orderBy('{$orderBy}')->get();";
            $compact[] = "'{$relation['options_variable']}'";
        }

        $compactList = implode(', ', $compact);

        return implode("\n", $loads)."\n\n        return view('{$viewBasePath}.{$viewBase}.create', compact({$compactList}));";
    }

    private function buildEditViewDataBlock(array $belongsToMeta, string $resourceVariable, string $viewBasePath, string $viewBase): string
    {
        if (empty($belongsToMeta)) {
            return "        return view('{$viewBasePath}.{$viewBase}.edit', compact('{$resourceVariable}'));";
        }

        $loads = [];
        $compact = ["'{$resourceVariable}'"];
        foreach ($belongsToMeta as $relation) {
            $orderBy = $relation['display_column'] ?? $relation['owner_key'];
            $loads[] = "        \${$relation['options_variable']} = {$relation['related_model']}::query()->orderBy('{$orderBy}')->get();";
            $compact[] = "'{$relation['options_variable']}'";
        }

        $compactList = implode(', ', $compact);

        return implode("\n", $loads)."\n\n        return view('{$viewBasePath}.{$viewBase}.edit', compact({$compactList}));";
    }

    private function buildSearchableColumns(array $table, array $fillable): array
    {
        $preferred = ['name', 'title', 'email', 'code', 'status', 'phone', 'description'];
        $searchable = [];

        foreach ($fillable as $column) {
            foreach ($preferred as $keyword) {
                if (str_contains($column, $keyword)) {
                    $searchable[] = $column;
                    break;
                }
            }
        }

        if (!empty($searchable)) {
            return array_values(array_unique($searchable));
        }

        foreach ($table['columns'] ?? [] as $column) {
            $name = (string) ($column['name'] ?? '');
            $type = strtolower((string) ($column['type'] ?? ''));
            if (in_array($type, ['varchar', 'char', 'string', 'text', 'tinytext', 'mediumtext', 'longtext'], true) && !in_array($name, ['created_at', 'updated_at', 'deleted_at'], true)) {
                $searchable[] = $name;
            }
        }

        return array_values(array_unique($searchable));
    }

    private function buildSortableColumns(array $table): array
    {
        $sortable = ['id', 'created_at', 'updated_at'];
        foreach ($table['columns'] ?? [] as $column) {
            $name = (string) ($column['name'] ?? '');
            $type = strtolower((string) ($column['type'] ?? ''));
            if ($name === '') {
                continue;
            }

            if (in_array($type, ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'decimal', 'float', 'double', 'date', 'datetime', 'timestamp', 'varchar', 'char', 'string'], true)) {
                $sortable[] = $name;
            }
        }

        return array_values(array_unique($sortable));
    }

    private function buildFillable(array $table): array
    {
        $primaryKeys = $table['primary_keys'] ?? [];

        return array_values(array_filter(array_map(function (array $column) use ($primaryKeys): ?string {
            $name = $column['name'];
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                return null;
            }

            if (in_array($name, $primaryKeys, true) && ($column['auto_increment'] ?? false)) {
                return null;
            }

            return $name;
        }, $table['columns'] ?? [])));
    }

    private function modelName(string $tableName): string
    {
        return Str::studly(Str::singular($tableName));
    }

    private function buildWithRelationsCode(array $belongsToMeta): string
    {
        $methods = array_values(array_unique(array_filter(array_map(
            static fn (array $relation): ?string => $relation['relation_method'] ?? null,
            $belongsToMeta
        ))));

        if (empty($methods)) {
            return '';
        }

        $export = '['.implode(', ', array_map(static fn (string $method): string => "'".$method."'", $methods)).']';

        return "        \$query->with({$export});";
    }

    private function withRelationsArrayCode(array $belongsToMeta): string
    {
        $methods = array_values(array_unique(array_filter(array_map(
            static fn (array $relation): ?string => $relation['relation_method'] ?? null,
            $belongsToMeta
        ))));

        if (empty($methods)) {
            return '[]';
        }

        return '['.implode(', ', array_map(static fn (string $method): string => "'".$method."'", $methods)).']';
    }

    private function resolveDisplayColumn(string $tableName, array $allTables): string
    {
        foreach ($allTables as $table) {
            if ((string) ($table['name'] ?? '') !== $tableName) {
                continue;
            }

            $columns = array_map(static fn (array $column): string => (string) ($column['name'] ?? ''), $table['columns'] ?? []);
            foreach (['name', 'title', 'label', 'code', 'email'] as $preferred) {
                if (in_array($preferred, $columns, true)) {
                    return $preferred;
                }
            }
        }

        return 'id';
    }
}
