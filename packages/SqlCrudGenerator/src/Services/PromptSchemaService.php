<?php

namespace SqlCrudGenerator\Services;

use Illuminate\Support\Str;

class PromptSchemaService
{
    public function parse(string $prompt, string $moduleName = 'Module'): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($prompt));
        if ($normalized === '') {
            return ['tables' => []];
        }

        $tables = $this->extractTablesFromStructuredBlocks($normalized);
        if (empty($tables)) {
            $tables = $this->extractTablesFromListStyle($normalized);
        }

        if (empty($tables)) {
            $tables = $this->fallbackSingleTable($moduleName);
        }

        $tables = $this->normalizeTables($tables, $moduleName);
        $tables = $this->inferForeignKeys($tables);

        return ['tables' => array_values($tables)];
    }

    private function extractTablesFromStructuredBlocks(string $prompt): array
    {
        $tables = [];
        $lines = explode("\n", $prompt);
        $currentTable = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^(table|entity)\s*:\s*([a-zA-Z0-9_]+)/i', $trimmed, $match)) {
                $tableName = Str::snake($match[2]);
                $currentTable = $tableName;
                if (!isset($tables[$tableName])) {
                    $tables[$tableName] = [
                        'name' => $tableName,
                        'columns' => [],
                        'primary_keys' => ['id'],
                        'foreign_keys' => [],
                    ];
                }
                continue;
            }

            if ($currentTable === null) {
                continue;
            }

            if (preg_match('/^(column|field)\s*:\s*([a-zA-Z0-9_]+)\s*(?:\(([^)]+)\))?/i', $trimmed, $match)) {
                $columnName = Str::snake($match[2]);
                $type = isset($match[3]) ? strtolower(trim($match[3])) : $this->guessTypeByName($columnName);

                $tables[$currentTable]['columns'][] = $this->buildColumn($columnName, $type);
                continue;
            }

            if (preg_match('/^-+\s*([a-zA-Z0-9_]+)\s*(?::|\-)\s*([a-zA-Z0-9()_ ]+)/', $trimmed, $match)) {
                $columnName = Str::snake($match[1]);
                $type = strtolower(trim($match[2]));
                $tables[$currentTable]['columns'][] = $this->buildColumn($columnName, $type);
            }
        }

        return $tables;
    }

    private function extractTablesFromListStyle(string $prompt): array
    {
        $tables = [];
        $tableNames = [];

        if (preg_match('/tables?\s*:\s*([a-zA-Z0-9_,\s]+)/i', $prompt, $match)) {
            $parts = preg_split('/[,]+/', $match[1]) ?: [];
            foreach ($parts as $part) {
                $name = Str::snake(trim($part));
                if ($name !== '') {
                    $tableNames[] = $name;
                }
            }
        }

        if (empty($tableNames)) {
            preg_match_all('/\b([a-zA-Z0-9_]+)\s+table\b/i', $prompt, $matches);
            foreach ($matches[1] ?? [] as $name) {
                $tableNames[] = Str::snake($name);
            }
        }

        $tableNames = array_values(array_unique(array_filter($tableNames)));

        foreach ($tableNames as $tableName) {
            $tables[$tableName] = [
                'name' => $tableName,
                'columns' => [],
                'primary_keys' => ['id'],
                'foreign_keys' => [],
            ];
        }

        return $tables;
    }

    private function fallbackSingleTable(string $moduleName): array
    {
        $table = Str::snake(Str::pluralStudly($moduleName));

        return [
            $table => [
                'name' => $table,
                'columns' => [],
                'primary_keys' => ['id'],
                'foreign_keys' => [],
            ],
        ];
    }

    private function normalizeTables(array $tables, string $moduleName): array
    {
        foreach ($tables as &$table) {
            $table['columns'] = $this->deduplicateColumns($table['columns']);
            $this->ensureBaseColumns($table, $moduleName);
        }

        return $tables;
    }

    private function deduplicateColumns(array $columns): array
    {
        $seen = [];
        $unique = [];

        foreach ($columns as $column) {
            $name = $column['name'] ?? '';
            if ($name === '' || isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;
            $unique[] = $column;
        }

        return $unique;
    }

    private function ensureBaseColumns(array &$table, string $moduleName): void
    {
        $existing = array_map(static fn ($column) => $column['name'], $table['columns']);

        if (!in_array('id', $existing, true)) {
            array_unshift($table['columns'], [
                'name' => 'id',
                'type' => 'bigint',
                'raw_type' => 'bigint',
                'nullable' => false,
                'auto_increment' => true,
                'default' => null,
            ]);
        }

        if (!$this->hasDescriptiveColumn($existing)) {
            $table['columns'][] = $this->buildColumn('name', 'varchar(255)', false);
        }

        if (!in_array('created_at', $existing, true)) {
            $table['columns'][] = $this->buildColumn('created_at', 'timestamp', true);
        }

        if (!in_array('updated_at', $existing, true)) {
            $table['columns'][] = $this->buildColumn('updated_at', 'timestamp', true);
        }
    }

    private function hasDescriptiveColumn(array $existing): bool
    {
        $labels = ['name', 'title', 'label', 'code', 'email'];
        foreach ($labels as $label) {
            if (in_array($label, $existing, true)) {
                return true;
            }
        }

        return false;
    }

    private function inferForeignKeys(array $tables): array
    {
        $tableNames = array_keys($tables);

        foreach ($tables as &$table) {
            foreach ($table['columns'] as $column) {
                $name = $column['name'] ?? '';
                if (!str_ends_with($name, '_id') || $name === 'id') {
                    continue;
                }

                $base = substr($name, 0, -3);
                $candidates = [
                    Str::snake(Str::pluralStudly($base)),
                    Str::snake($base),
                ];

                foreach ($candidates as $candidate) {
                    if (!in_array($candidate, $tableNames, true)) {
                        continue;
                    }

                    $table['foreign_keys'][] = [
                        'column' => $name,
                        'references_table' => $candidate,
                        'references_column' => 'id',
                    ];
                    break;
                }
            }

            $table['foreign_keys'] = $this->deduplicateForeignKeys($table['foreign_keys']);
        }

        return $tables;
    }

    private function deduplicateForeignKeys(array $foreignKeys): array
    {
        $seen = [];
        $unique = [];

        foreach ($foreignKeys as $foreignKey) {
            $signature = implode(':', [
                $foreignKey['column'] ?? '',
                $foreignKey['references_table'] ?? '',
                $foreignKey['references_column'] ?? '',
            ]);

            if ($signature === '::' || isset($seen[$signature])) {
                continue;
            }

            $seen[$signature] = true;
            $unique[] = $foreignKey;
        }

        return $unique;
    }

    private function guessTypeByName(string $name): string
    {
        if (str_ends_with($name, '_id')) {
            return 'bigint';
        }

        if (str_contains($name, 'date') || str_contains($name, '_at')) {
            return 'timestamp';
        }

        if (str_starts_with($name, 'is_') || str_starts_with($name, 'has_')) {
            return 'boolean';
        }

        if (str_contains($name, 'amount') || str_contains($name, 'price') || str_contains($name, 'total')) {
            return 'decimal(10,2)';
        }

        if (str_contains($name, 'description') || str_contains($name, 'note')) {
            return 'text';
        }

        return 'varchar(255)';
    }

    private function buildColumn(string $name, string $rawType, bool $nullable = true): array
    {
        $type = strtolower($rawType);
        $baseType = preg_replace('/\s*\(.*$/', '', $type);
        $baseType = str_replace(' unsigned', '', (string) $baseType);

        return [
            'name' => $name,
            'type' => trim((string) $baseType),
            'raw_type' => trim($type),
            'nullable' => $nullable,
            'auto_increment' => $name === 'id',
            'default' => null,
        ];
    }
}
