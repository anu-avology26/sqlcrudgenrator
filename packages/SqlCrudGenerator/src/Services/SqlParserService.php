<?php

namespace SqlCrudGenerator\Services;

class SqlParserService
{
    public function parse(string $sql): array
    {
        $normalizedSql = str_replace(["\r\n", "\r"], "\n", $sql);
        preg_match_all('/CREATE\s+TABLE\s+`?([a-zA-Z0-9_]+)`?\s*\((.*?)\)\s*(?:ENGINE|COMMENT|DEFAULT|CHARSET|COLLATE|ROW_FORMAT|;)/is', $normalizedSql, $matches, PREG_SET_ORDER);

        $tables = [];
        foreach ($matches as $match) {
            $definitions = $this->splitDefinitions($match[2]);
            $table = [
                'name' => $match[1],
                'columns' => [],
                'primary_keys' => [],
                'foreign_keys' => [],
            ];

            foreach ($definitions as $definition) {
                $line = trim($definition);
                if ($line === '') {
                    continue;
                }

                if ($this->isPrimaryKeyLine($line)) {
                    $table['primary_keys'] = array_values(array_unique(array_merge(
                        $table['primary_keys'],
                        $this->extractColumnsFromConstraint($line)
                    )));
                    continue;
                }

                if ($this->isForeignKeyLine($line)) {
                    $foreign = $this->extractForeignKey($line);
                    if ($foreign !== null) {
                        $table['foreign_keys'][] = $foreign;
                    }
                    continue;
                }

                if ($this->isSecondaryIndexLine($line)) {
                    continue;
                }

                $column = $this->extractColumn($line);
                if ($column === null) {
                    continue;
                }

                $table['columns'][] = $column;

                if (stripos($line, 'PRIMARY KEY') !== false) {
                    $table['primary_keys'][] = $column['name'];
                }

                $inlineForeign = $this->extractInlineForeignKey($line, $column['name']);
                if ($inlineForeign !== null) {
                    $table['foreign_keys'][] = $inlineForeign;
                }
            }

            $table['primary_keys'] = array_values(array_unique($table['primary_keys']));
            $table['foreign_keys'] = $this->deduplicateForeignKeys($table['foreign_keys']);
            $tables[] = $table;
        }

        return ['tables' => $tables];
    }

    private function splitDefinitions(string $body): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $length = strlen($body);

        for ($i = 0; $i < $length; $i++) {
            $char = $body[$i];
            if ($char === '(') {
                $depth++;
            } elseif ($char === ')' && $depth > 0) {
                $depth--;
            }

            if ($char === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $parts[] = $current;
        }

        return $parts;
    }

    private function isPrimaryKeyLine(string $line): bool
    {
        return preg_match('/^(PRIMARY\s+KEY|CONSTRAINT\s+`?[a-zA-Z0-9_]+`?\s+PRIMARY\s+KEY)/i', $line) === 1;
    }

    private function isForeignKeyLine(string $line): bool
    {
        return preg_match('/^(CONSTRAINT\s+`?[a-zA-Z0-9_]+`?\s+)?FOREIGN\s+KEY/i', $line) === 1;
    }

    private function isSecondaryIndexLine(string $line): bool
    {
        return preg_match('/^(UNIQUE\s+KEY|UNIQUE\s+INDEX|KEY|INDEX)/i', $line) === 1;
    }

    private function extractColumnsFromConstraint(string $line): array
    {
        if (!preg_match('/PRIMARY\s+KEY\s*\(([^)]+)\)/i', $line, $matches)) {
            return [];
        }

        return $this->parseIdentifierList($matches[1]);
    }

    private function extractForeignKey(string $line): ?array
    {
        if (!preg_match('/FOREIGN\s+KEY\s*\(([^)]+)\)\s*REFERENCES\s+`?([a-zA-Z0-9_]+)`?\s*\(([^)]+)\)/i', $line, $matches)) {
            return null;
        }

        $localColumns = $this->parseIdentifierList($matches[1]);
        $referenceColumns = $this->parseIdentifierList($matches[3]);

        if (empty($localColumns) || empty($referenceColumns)) {
            return null;
        }

        return [
            'column' => $localColumns[0],
            'references_table' => $matches[2],
            'references_column' => $referenceColumns[0],
        ];
    }

    private function parseIdentifierList(string $value): array
    {
        $parts = explode(',', $value);

        return array_values(array_filter(array_map(function (string $part): string {
            return trim(str_replace('`', '', $part));
        }, $parts)));
    }

    private function extractColumn(string $line): ?array
    {
        $trimmed = trim($line);

        if (!preg_match('/^`?([a-zA-Z0-9_]+)`?\s+([a-zA-Z]+(?:\s*\([^)]+\))?(?:\s+unsigned)?)/i', $trimmed, $matches)) {
            return null;
        }

        $rawType = strtolower(trim($matches[2]));
        $baseType = strtolower(preg_replace('/\s*\(.*$/', '', $rawType));
        $baseType = trim(str_replace(' unsigned', '', $baseType));
        $attributes = strtolower(substr($trimmed, strlen($matches[0])));

        return [
            'name' => $matches[1],
            'type' => $baseType,
            'raw_type' => $rawType,
            'nullable' => stripos($attributes, 'not null') === false,
            'auto_increment' => stripos($attributes, 'auto_increment') !== false,
            'default' => $this->extractDefaultValue($attributes),
        ];
    }

    private function extractDefaultValue(string $attributes): ?string
    {
        if (!preg_match('/default\s+([^\s,]+)/i', $attributes, $matches)) {
            return null;
        }

        return trim($matches[1], "'\"");
    }

    private function extractInlineForeignKey(string $line, string $columnName): ?array
    {
        if (!preg_match('/REFERENCES\s+`?([a-zA-Z0-9_]+)`?\s*\(([^)]+)\)/i', $line, $matches)) {
            return null;
        }

        $referenceColumns = $this->parseIdentifierList($matches[2]);
        if (empty($referenceColumns)) {
            return null;
        }

        return [
            'column' => $columnName,
            'references_table' => $matches[1],
            'references_column' => $referenceColumns[0],
        ];
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

            if (isset($seen[$signature])) {
                continue;
            }

            $seen[$signature] = true;
            $unique[] = $foreignKey;
        }

        return $unique;
    }
}
