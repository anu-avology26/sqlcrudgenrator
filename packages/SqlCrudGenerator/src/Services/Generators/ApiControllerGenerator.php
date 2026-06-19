<?php

namespace SqlCrudGenerator\Services\Generators;

use Illuminate\Support\Str;

class ApiControllerGenerator
{
    public function generate(array $schema, array $context): array
    {
        if (!in_array($context['crud_mode'], ['api', 'both'], true)) {
            return [];
        }

        $controllers = [];
        $apiControllersNamespace = $context['controllers_namespace'].'\\Api';
        $notificationsEnabled = $context['enable_email_notifications'] ? 'true' : 'false';
        $notificationEmail = addslashes($context['notification_email'] ?? '');
        $importExportEnabled = $context['enable_import_export'] ? 'true' : 'false';

        foreach ($schema['tables'] ?? [] as $table) {
            $modelName = $this->modelName($table['name']);
            $controllerName = $modelName.'ApiController';
            $storeRequest = 'Store'.$modelName.'Request';
            $updateRequest = 'Update'.$modelName.'Request';
            $resourceVariable = Str::camel($modelName);
            $resourceCollection = Str::camel(Str::pluralStudly($modelName));
            $modelNamespace = $context['models_namespace'].'\\'.$modelName;
            $requestNamespace = $context['requests_namespace'].'\\';
            $fillable = $this->buildFillable($table);
            $searchableColumns = $this->buildSearchableColumns($table, $fillable);
            $sortableColumns = $this->buildSortableColumns($table);
            $fillableArrayCode = empty($fillable) ? '[]' : "['".implode("', '", $fillable)."']";
            $searchableArrayCode = empty($searchableColumns) ? '[]' : "['".implode("', '", $searchableColumns)."']";
            $sortableArrayCode = empty($sortableColumns) ? '[]' : "['".implode("', '", $sortableColumns)."']";
            $exportImportMethods = str_replace(
                ['{MODEL}', '{MODEL_PLURAL}', '{FILLABLE}', '{IMPORT_EXPORT_ENABLED}'],
                [$modelName, Str::pluralStudly($modelName), $fillableArrayCode, $importExportEnabled],
                $this->exportImportMethods()
            );

            $controllers['Api/'.$controllerName.'.php'] = <<<PHP
<?php

namespace {$apiControllersNamespace};

use App\Http\Controllers\Controller;
use {$requestNamespace}{$storeRequest};
use {$requestNamespace}{$updateRequest};
use {$modelNamespace};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class {$controllerName} extends Controller
{
    public function index(Request \$request): JsonResponse
    {
        \$query = {$modelName}::query();
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

        \${$resourceCollection} = \$query->orderBy(\$sort, \$direction)->paginate(15);

        return response()->json(\${$resourceCollection});
    }

    public function store({$storeRequest} \$request): JsonResponse
    {
        \${$resourceVariable} = {$modelName}::query()->create(\$request->validated());
        \$this->sendCrudNotification('created', \${$resourceVariable});

        return response()->json([
            'message' => '{$modelName} created successfully.',
            'data' => \${$resourceVariable},
        ], 201);
    }

    public function show(int|string \$id): JsonResponse
    {
        \${$resourceVariable} = {$modelName}::query()->findOrFail(\$id);

        return response()->json(\${$resourceVariable});
    }

    public function update({$updateRequest} \$request, int|string \$id): JsonResponse
    {
        \${$resourceVariable} = {$modelName}::query()->findOrFail(\$id);
        \${$resourceVariable}->update(\$request->validated());
        \$this->sendCrudNotification('updated', \${$resourceVariable});

        return response()->json([
            'message' => '{$modelName} updated successfully.',
            'data' => \${$resourceVariable},
        ]);
    }

    public function destroy(int|string \$id): JsonResponse
    {
        \${$resourceVariable} = {$modelName}::query()->findOrFail(\$id);
        \${$resourceVariable}->delete();

        return response()->json(['message' => '{$modelName} deleted successfully.']);
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

    public function import(Request $request): JsonResponse
    {
        if ({IMPORT_EXPORT_ENABLED} !== true) {
            return response()->json(['message' => 'Import/export is disabled.'], 422);
        }

        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt']]);

        $fillable = {FILLABLE};
        $path = $request->file('csv_file')->getRealPath();
        if ($path === false) {
            return response()->json(['message' => 'Unable to read uploaded CSV file.'], 422);
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return response()->json(['message' => 'Unable to open uploaded CSV file.'], 422);
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            return response()->json(['message' => 'CSV header is missing.'], 422);
        }

        $created = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $payload = [];
            foreach ($fillable as $index => $column) {
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
                $entity = {MODEL}::query()->create($payload);
                $this->sendCrudNotification('imported', $entity);
                $created++;
            }
        }

        fclose($handle);

        return response()->json([
            'message' => '{MODEL} import completed.',
            'created_count' => $created,
        ]);
    }
PHP;
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
}
