<?php

namespace SqlCrudGenerator\Services\Generators;

use Illuminate\Support\Str;

class ModelGenerator
{
    public function generate(array $schema, array $context): array
    {
        $models = [];
        $modelNamespace = $context['models_namespace'];
        $tables = $schema['tables'] ?? [];

        foreach ($tables as $table) {
            $modelName = $this->modelName($table['name']);
            $fillable = $this->buildFillable($table);
            $casts = $this->buildCasts($table);
            $belongsToRelations = $this->buildBelongsToRelations($table, $tables);
            $hasManyRelations = $this->buildHasManyRelations($table, $tables);
            $primaryMeta = $this->resolvePrimaryMeta($table);
            $timestampsBlock = $this->buildTimestampsBlock($table, $context);

            $imports = [
                'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
                'Illuminate\\Database\\Eloquent\\Model',
            ];

            if (!empty($belongsToRelations)) {
                $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo';
            }

            if (!empty($hasManyRelations)) {
                $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\HasMany';
            }

            $imports = array_values(array_unique($imports));
            sort($imports);

            $relatedImports = [];
            foreach (array_merge($belongsToRelations, $hasManyRelations) as $relation) {
                if (!isset($relation['related_model']) || $relation['related_model'] === $modelName) {
                    continue;
                }

                $relatedImports[] = $modelNamespace.'\\'.$relation['related_model'];
            }
            $relatedImports = array_values(array_unique($relatedImports));
            sort($relatedImports);

            $allImports = array_merge($imports, $relatedImports);
            $importLines = implode("\n", array_map(fn (string $import): string => 'use '.$import.';', $allImports));

            $fillableExport = implode(",\n        ", array_map(fn (string $field): string => "'".$field."'", $fillable));
            $fillableBlock = $fillableExport !== '' ? "        ".$fillableExport."\n    " : '';
            $primaryBlock = $this->buildPrimaryBlock($primaryMeta);
            $castsBlock = $this->buildCastsBlock($casts);
            $searchableBlock = $this->buildArrayPropertyBlock('searchable', $this->buildSearchableFields($fillable));
            $sortableBlock = $this->buildArrayPropertyBlock('sortable', $this->buildSortableFields($table));

            $relationMethods = [];
            foreach ($belongsToRelations as $relation) {
                $relationMethods[] = <<<PHP

    public function {$relation['method']}(): BelongsTo
    {
        return \$this->belongsTo({$relation['related_model']}::class, '{$relation['foreign_key']}', '{$relation['owner_key']}');
    }
PHP;
            }

            foreach ($hasManyRelations as $relation) {
                $relationMethods[] = <<<PHP

    public function {$relation['method']}(): HasMany
    {
        return \$this->hasMany({$relation['related_model']}::class, '{$relation['foreign_key']}', '{$relation['local_key']}');
    }
PHP;
            }

            $relationBlock = empty($relationMethods) ? '' : "\n".implode("\n", $relationMethods);

            $models[$modelName.'.php'] = <<<PHP
<?php

namespace {$modelNamespace};

{$importLines}

class {$modelName} extends Model
{
    use HasFactory;

    protected \$table = '{$table['name']}';
{$primaryBlock}
{$timestampsBlock}

    protected \$fillable = [
{$fillableBlock}];
{$castsBlock}
{$searchableBlock}
{$sortableBlock}
{$relationBlock}
}
PHP;
        }

        return $models;
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

    private function buildBelongsToRelations(array $table, array $allTables): array
    {
        $relations = [];
        $seen = [];
        foreach ($table['foreign_keys'] ?? [] as $foreignKey) {
            $column = (string) ($foreignKey['column'] ?? '');
            $relations[] = [
                'method' => Str::camel(Str::singular(preg_replace('/_id$/', '', $foreignKey['column']))),
                'related_model' => $this->modelName($foreignKey['references_table']),
                'foreign_key' => $column,
                'owner_key' => $foreignKey['references_column'],
            ];
            $seen[] = $column;
        }

        foreach ($table['columns'] ?? [] as $column) {
            $name = (string) ($column['name'] ?? '');
            if ($name === '' || !Str::endsWith($name, '_id') || in_array($name, $seen, true)) {
                continue;
            }

            $resolvedTable = $this->resolveReferenceTableName($name, (string) ($table['name'] ?? ''), $allTables);
            if ($resolvedTable === null) {
                continue;
            }

            $relations[] = [
                'method' => Str::camel(Str::singular(preg_replace('/_id$/', '', $name))),
                'related_model' => $this->modelName($resolvedTable),
                'foreign_key' => $name,
                'owner_key' => 'id',
            ];
        }

        return $relations;
    }

    private function buildHasManyRelations(array $table, array $allTables): array
    {
        $relations = [];
        $currentTable = $table['name'];

        foreach ($allTables as $otherTable) {
            foreach ($otherTable['foreign_keys'] ?? [] as $foreignKey) {
                if (($foreignKey['references_table'] ?? null) !== $currentTable) {
                    continue;
                }

                $relatedModel = $this->modelName($otherTable['name']);
                $relations[] = [
                    'method' => Str::camel(Str::plural(Str::snake($relatedModel))),
                    'related_model' => $relatedModel,
                    'foreign_key' => $foreignKey['column'],
                    'local_key' => $foreignKey['references_column'] ?? 'id',
                ];
            }
        }

        return $relations;
    }

    private function modelName(string $tableName): string
    {
        return Str::studly(Str::singular($tableName));
    }

    private function resolvePrimaryMeta(array $table): array
    {
        $primaryKeys = $table['primary_keys'] ?? [];
        if (count($primaryKeys) !== 1) {
            return ['name' => null, 'incrementing' => true, 'key_type' => 'int'];
        }

        $primaryKey = $primaryKeys[0];
        $column = null;
        foreach ($table['columns'] ?? [] as $candidate) {
            if (($candidate['name'] ?? null) === $primaryKey) {
                $column = $candidate;
                break;
            }
        }

        $type = strtolower((string) ($column['type'] ?? ''));
        $autoIncrement = (bool) ($column['auto_increment'] ?? false);
        $isNumeric = in_array($type, ['tinyint', 'smallint', 'int', 'integer', 'bigint'], true);

        return [
            'name' => $primaryKey,
            'incrementing' => $autoIncrement || ($primaryKey === 'id' && $isNumeric),
            'key_type' => $isNumeric ? 'int' : 'string',
        ];
    }

    private function buildPrimaryBlock(array $primaryMeta): string
    {
        $primaryName = $primaryMeta['name'] ?? null;
        if ($primaryName === null || $primaryName === 'id') {
            return '';
        }

        $lines = [
            "    protected \$primaryKey = '{$primaryName}';",
        ];

        if (($primaryMeta['incrementing'] ?? true) === false) {
            $lines[] = '    public $incrementing = false;';
        }

        if (($primaryMeta['key_type'] ?? 'int') === 'string') {
            $lines[] = "    protected \$keyType = 'string';";
        }

        return "\n".implode("\n", $lines)."\n";
    }

    private function buildTimestampsBlock(array $table, array $context): string
    {
        // Generator migrations always add timestamps(). Keep model behavior aligned.
        if (($context['generate_migrations'] ?? true) === true) {
            return '';
        }

        $columnNames = array_map(static fn (array $column): string => (string) ($column['name'] ?? ''), $table['columns'] ?? []);
        $hasCreatedAt = in_array('created_at', $columnNames, true);
        $hasUpdatedAt = in_array('updated_at', $columnNames, true);

        if ($hasCreatedAt && $hasUpdatedAt) {
            return '';
        }

        return "\n    public \$timestamps = false;\n";
    }

    private function buildCasts(array $table): array
    {
        $casts = [];

        foreach ($table['columns'] ?? [] as $column) {
            $name = (string) ($column['name'] ?? '');
            $type = strtolower((string) ($column['type'] ?? ''));
            $rawType = strtolower((string) ($column['raw_type'] ?? ''));

            if ($name === '') {
                continue;
            }

            if ($this->isBooleanLikeColumn($name, $type, $rawType)) {
                $casts[$name] = 'boolean';
                continue;
            }

            if (in_array($type, ['int', 'integer', 'bigint', 'smallint', 'tinyint'], true)) {
                $casts[$name] = 'integer';
                continue;
            }

            if (in_array($type, ['decimal', 'float', 'double'], true)) {
                $casts[$name] = 'decimal:2';
                continue;
            }

            if ($type === 'date') {
                $casts[$name] = 'date';
                continue;
            }

            if (in_array($type, ['datetime', 'timestamp'], true)) {
                $casts[$name] = 'datetime';
                continue;
            }

            if ($type === 'json') {
                $casts[$name] = 'array';
            }
        }

        return $casts;
    }

    private function buildCastsBlock(array $casts): string
    {
        if (empty($casts)) {
            return '';
        }

        $lines = [];
        foreach ($casts as $column => $castType) {
            $lines[] = "        '".$column."' => '".$castType."',";
        }

        return "\n    protected \$casts = [\n".implode("\n", $lines)."\n    ];\n";
    }

    private function buildArrayPropertyBlock(string $property, array $values): string
    {
        if (empty($values)) {
            return '';
        }

        $lines = implode(",\n        ", array_map(static fn (string $value): string => "'".$value."'", $values));

        return "\n    public array \${$property} = [\n        {$lines}\n    ];\n";
    }

    private function buildSearchableFields(array $fillable): array
    {
        $keywords = ['name', 'title', 'email', 'code', 'status', 'phone'];

        return array_values(array_filter($fillable, static function (string $column) use ($keywords): bool {
            foreach ($keywords as $keyword) {
                if (str_contains($column, $keyword)) {
                    return true;
                }
            }

            return false;
        }));
    }

    private function buildSortableFields(array $table): array
    {
        $sortable = ['id', 'created_at', 'updated_at'];
        foreach ($table['columns'] ?? [] as $column) {
            $name = (string) ($column['name'] ?? '');
            $type = strtolower((string) ($column['type'] ?? ''));
            if ($name === '') {
                continue;
            }

            if (in_array($type, ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'decimal', 'float', 'double', 'date', 'datetime', 'timestamp'], true)) {
                $sortable[] = $name;
            }
        }

        return array_values(array_unique($sortable));
    }

    private function isBooleanLikeColumn(string $name, string $type, string $rawType): bool
    {
        if ($type !== 'tinyint' && $type !== 'boolean' && $type !== 'bool') {
            return false;
        }

        if (str_contains($rawType, '(1)')) {
            return true;
        }

        return Str::startsWith($name, ['is_', 'has_', 'can_'])
            || Str::endsWith($name, ['_flag', '_active', '_enabled', '_verified', '_status']);
    }

    private function resolveReferenceTableName(string $column, string $currentTable, array $allTables): ?string
    {
        $base = preg_replace('/_id$/', '', $column) ?: $column;
        $pluralBase = Str::snake(Str::pluralStudly(Str::studly($base)));
        $singularBase = Str::snake(Str::singular($base));
        $tableNames = array_values(array_map(static fn (array $table): string => (string) ($table['name'] ?? ''), $allTables));

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
}
