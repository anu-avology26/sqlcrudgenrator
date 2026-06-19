<?php

namespace SqlCrudGenerator\Services\Generators;

use Illuminate\Support\Str;

class RequestGenerator
{
    public function generate(array $schema, array $context): array
    {
        $requests = [];
        $requestNamespace = $context['requests_namespace'];

        foreach ($schema['tables'] ?? [] as $table) {
            $modelName = $this->modelName($table['name']);
            $storeClass = 'Store'.$modelName.'Request';
            $updateClass = 'Update'.$modelName.'Request';

            $requests[$storeClass.'.php'] = $this->buildRequestClass($requestNamespace, $storeClass, $this->buildRules($table, false));
            $requests[$updateClass.'.php'] = $this->buildRequestClass($requestNamespace, $updateClass, $this->buildRules($table, true));
        }

        return $requests;
    }

    private function buildRequestClass(string $namespace, string $className, array $rules): string
    {
        $ruleLines = [];
        foreach ($rules as $column => $rule) {
            $ruleLines[] = "            '".$column."' => '".$rule."',";
        }

        $rulesBlock = empty($ruleLines) ? '            // No fillable columns detected.' : implode("\n", $ruleLines);

        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Foundation\Http\FormRequest;

class {$className} extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
{$rulesBlock}
        ];
    }
}
PHP;
    }

    private function buildRules(array $table, bool $isUpdate): array
    {
        $rules = [];
        $primaryKeys = $table['primary_keys'] ?? [];

        foreach ($table['columns'] ?? [] as $column) {
            $name = $column['name'] ?? '';
            $type = strtolower($column['type'] ?? '');
            $rawType = strtolower($column['raw_type'] ?? '');

            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            if (in_array($name, $primaryKeys, true) && ($column['auto_increment'] ?? false)) {
                continue;
            }

            $rules[$name] = $this->ruleForColumn($name, $type, $rawType, $column, $isUpdate);
        }

        return $rules;
    }

    private function ruleForColumn(string $name, string $type, string $rawType, array $column, bool $isUpdate): string
    {
        $parts = [];
        $isNullable = (bool) ($column['nullable'] ?? false);

        if ($isUpdate) {
            $parts[] = 'sometimes';
            $parts[] = $isNullable ? 'nullable' : 'required';
        } else {
            $parts[] = $isNullable ? 'nullable' : 'required';
        }

        if (Str::contains($name, 'email')) {
            $parts[] = 'email';
            $parts[] = 'max:255';

            return implode('|', array_unique($parts));
        }

        if (Str::endsWith($name, '_id')) {
            $parts[] = 'integer';

            return implode('|', array_unique($parts));
        }

        if (in_array($name, ['name', 'title'], true)) {
            $parts[] = 'string';
            $parts[] = 'max:255';

            return implode('|', array_unique($parts));
        }

        if (in_array($type, ['text', 'tinytext', 'mediumtext', 'longtext'], true)) {
            $parts[] = 'string';

            return implode('|', array_unique($parts));
        }

        if (in_array($type, ['varchar', 'char', 'string'], true)) {
            $parts[] = 'string';
            $parts[] = 'max:255';

            return implode('|', array_unique($parts));
        }

        if (in_array($type, ['int', 'integer', 'bigint', 'smallint', 'tinyint'], true)) {
            if ($this->isBooleanLikeColumn($name, $type, $rawType)) {
                $parts[] = 'boolean';
            } else {
                $parts[] = 'integer';
            }

            return implode('|', array_unique($parts));
        }

        if (in_array($type, ['decimal', 'float', 'double'], true)) {
            $parts[] = 'numeric';

            return implode('|', array_unique($parts));
        }

        if (in_array($type, ['date', 'datetime', 'timestamp'], true)) {
            $parts[] = 'date';

            return implode('|', array_unique($parts));
        }

        if ($type === 'enum') {
            $values = $this->extractEnumValues($rawType);
            if (!empty($values)) {
                $parts[] = 'in:'.implode(',', array_map(static fn (string $value): string => str_replace(',', '\,', $value), $values));
            } else {
                $parts[] = 'string';
            }

            return implode('|', array_unique($parts));
        }

        $parts[] = 'string';

        return implode('|', array_unique($parts));
    }

    private function extractEnumValues(string $rawType): array
    {
        if (!preg_match('/enum\s*\((.*)\)$/i', $rawType, $matches)) {
            return [];
        }

        $inside = $matches[1];
        $parts = str_getcsv($inside, ',', "'");

        return array_values(array_filter(array_map(static fn (string $value): string => trim($value), $parts)));
    }

    private function modelName(string $tableName): string
    {
        return Str::studly(Str::singular($tableName));
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
}
