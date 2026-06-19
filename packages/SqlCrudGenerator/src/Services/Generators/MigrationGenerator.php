<?php

namespace SqlCrudGenerator\Services\Generators;

class MigrationGenerator
{
    public function generate(array $schema, array $context): array
    {
        if (($context['generate_migrations'] ?? true) !== true) {
            return [];
        }

        $migrations = [];
        $moduleSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $context['module_name'])) ?: 'module';

        foreach ($schema['tables'] ?? [] as $table) {
            $tableName = $table['name'];
            $className = 'Create'.str_replace(' ', '', ucwords(str_replace('_', ' ', $tableName))).'Table';
            $fileName = "create_{$tableName}_table.php";

            [$columnLines, $needsDbImport] = $this->buildColumnLines($table);
            $foreignLines = $this->buildForeignLines($table);

            $allLines = array_merge($columnLines, $foreignLines);
            $body = implode("\n", array_map(static fn (string $line): string => '            '.$line, $allLines));

            $imports = [
                'use Illuminate\\Database\\Migrations\\Migration;',
                'use Illuminate\\Database\\Schema\\Blueprint;',
                'use Illuminate\\Support\\Facades\\Schema;',
            ];
            if ($needsDbImport) {
                $imports[] = 'use Illuminate\\Support\\Facades\\DB;';
            }

            $importBlock = implode("\n", $imports);

            $migrations[$moduleSlug.'/'.$fileName] = <<<PHP
<?php

{$importBlock}

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('{$tableName}');
        Schema::enableForeignKeyConstraints();

        Schema::create('{$tableName}', function (Blueprint \$table): void {
{$body}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
        }

        return $migrations;
    }

    private function buildColumnLines(array $table): array
    {
        $lines = [];
        $needsDbImport = false;
        $primaryKeys = $table['primary_keys'] ?? [];
        $columns = $table['columns'] ?? [];
        $columnMap = [];

        foreach ($columns as $column) {
            $columnMap[$column['name']] = $column;
        }

        $hasCreatedAt = isset($columnMap['created_at']);
        $hasUpdatedAt = isset($columnMap['updated_at']);
        $hasDeletedAt = isset($columnMap['deleted_at']);

        $hasCompositePrimary = count($primaryKeys) > 1;

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = strtolower($column['type'] ?? '');
            $rawType = strtolower($column['raw_type'] ?? '');
            $nullable = (bool) ($column['nullable'] ?? false);
            $default = $column['default'] ?? null;
            $autoIncrement = (bool) ($column['auto_increment'] ?? false);
            $isPrimary = in_array($name, $primaryKeys, true);

            if ($name === 'created_at' || $name === 'updated_at') {
                continue;
            }
            if ($name === 'deleted_at' && $hasDeletedAt) {
                continue;
            }

            $line = $this->columnLine($name, $type, $rawType, $autoIncrement, $isPrimary, $hasCompositePrimary);
            if ($line === '') {
                continue;
            }

            if ($isPrimary && !$hasCompositePrimary && !$this->isIdShortcut($line) && !str_contains($line, '->primary()')) {
                $line .= '->primary()';
            }

            if ($nullable && !$isPrimary && !$this->isIdShortcut($line)) {
                $line .= '->nullable()';
            }

            if ($default !== null && !$isPrimary && !$this->isIdShortcut($line)) {
                if (strtoupper($default) === 'CURRENT_TIMESTAMP') {
                    $line .= '->default(DB::raw(\'CURRENT_TIMESTAMP\'))';
                    $needsDbImport = true;
                } elseif (is_numeric($default)) {
                    $line .= '->default('.$default.')';
                } else {
                    $escaped = addslashes((string) $default);
                    $line .= "->default('{$escaped}')";
                }
            }

            $lines[] = $line.';';
        }

        if ($hasCompositePrimary) {
            $export = '['.implode(', ', array_map(static fn (string $key): string => "'".$key."'", $primaryKeys)).']';
            $lines[] = '$table->primary('.$export.');';
        }

        // Keep Eloquent CRUD stable by always creating both timestamp columns.
        $lines[] = '$table->timestamps();';

        if ($hasDeletedAt) {
            $lines[] = '$table->softDeletes();';
        }

        return [$lines, $needsDbImport];
    }

    private function buildForeignLines(array $table): array
    {
        $lines = [];
        foreach ($table['foreign_keys'] ?? [] as $foreign) {
            $column = $foreign['column'] ?? null;
            $refTable = $foreign['references_table'] ?? null;
            $refColumn = $foreign['references_column'] ?? null;

            if (!$column || !$refTable || !$refColumn) {
                continue;
            }

            $lines[] = "\$table->foreign('{$column}')->references('{$refColumn}')->on('{$refTable}');";
        }

        return $lines;
    }

    private function columnLine(string $name, string $type, string $rawType, bool $autoIncrement, bool $isPrimary, bool $hasCompositePrimary): string
    {
        if ($name === 'id' && ($autoIncrement || ($isPrimary && !$hasCompositePrimary))) {
            return '$table->id()';
        }

        if ($autoIncrement && $type === 'bigint') {
            return "\$table->bigIncrements('{$name}')";
        }
        if ($autoIncrement && in_array($type, ['int', 'integer'], true)) {
            return "\$table->increments('{$name}')";
        }

        if (in_array($type, ['varchar', 'char', 'string'], true)) {
            $length = $this->extractSingleNumber($rawType) ?? 255;
            return "\$table->string('{$name}', {$length})";
        }

        if ($type === 'text' || $type === 'tinytext' || $type === 'mediumtext' || $type === 'longtext') {
            return "\$table->text('{$name}')";
        }

        if ($type === 'bigint') {
            return "\$table->unsignedBigInteger('{$name}')";
        }

        if ($type === 'int' || $type === 'integer') {
            return "\$table->integer('{$name}')";
        }

        if ($this->isBooleanLikeColumn($name, $type, $rawType)) {
            return "\$table->boolean('{$name}')";
        }

        if ($type === 'tinyint') {
            return "\$table->tinyInteger('{$name}')";
        }

        if ($type === 'smallint') {
            return "\$table->smallInteger('{$name}')";
        }

        if ($type === 'decimal') {
            [$precision, $scale] = $this->extractPrecisionScale($rawType);
            return "\$table->decimal('{$name}', {$precision}, {$scale})";
        }

        if ($type === 'float') {
            return "\$table->float('{$name}')";
        }

        if ($type === 'double') {
            return "\$table->double('{$name}')";
        }

        if ($type === 'date') {
            return "\$table->date('{$name}')";
        }

        if ($type === 'datetime') {
            return "\$table->dateTime('{$name}')";
        }

        if ($type === 'timestamp') {
            return "\$table->timestamp('{$name}')";
        }

        if ($type === 'json') {
            return "\$table->json('{$name}')";
        }

        if ($type === 'enum') {
            $values = $this->extractEnumValues($rawType);
            if (!empty($values)) {
                $export = '['.implode(', ', array_map(static fn (string $v): string => "'".addslashes($v)."'", $values)).']';
                return "\$table->enum('{$name}', {$export})";
            }
        }

        $line = "\$table->string('{$name}', 255)";
        if ($isPrimary) {
            $line .= '->primary()';
        }

        return $line;
    }

    private function extractSingleNumber(string $rawType): ?int
    {
        if (!preg_match('/\((\d+)\)/', $rawType, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function extractPrecisionScale(string $rawType): array
    {
        if (!preg_match('/\((\d+)\s*,\s*(\d+)\)/', $rawType, $matches)) {
            return [10, 2];
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    private function extractEnumValues(string $rawType): array
    {
        if (!preg_match('/enum\s*\((.*)\)$/i', $rawType, $matches)) {
            return [];
        }

        $inside = $matches[1];
        $parts = str_getcsv($inside, ',', "'");

        return array_values(array_filter(array_map(static fn (string $v): string => trim($v), $parts)));
    }

    private function isIdShortcut(string $line): bool
    {
        return str_contains($line, '$table->id()')
            || str_contains($line, '$table->bigIncrements(')
            || str_contains($line, '$table->increments(');
    }

    private function isBooleanLikeColumn(string $name, string $type, string $rawType): bool
    {
        if ($type !== 'tinyint' && $type !== 'boolean' && $type !== 'bool') {
            return false;
        }

        if (str_contains($rawType, '(1)')) {
            return true;
        }

        return str_starts_with($name, 'is_')
            || str_starts_with($name, 'has_')
            || str_starts_with($name, 'can_')
            || str_ends_with($name, '_flag')
            || str_ends_with($name, '_active')
            || str_ends_with($name, '_enabled')
            || str_ends_with($name, '_verified')
            || str_ends_with($name, '_status');
    }
}
