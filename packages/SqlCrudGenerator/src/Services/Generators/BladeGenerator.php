<?php

namespace SqlCrudGenerator\Services\Generators;

use Illuminate\Support\Str;

class BladeGenerator
{
    public function generate(array $schema, array $context): array
    {
        if (!in_array($context['crud_mode'], ['web', 'both'], true)) {
            return [];
        }

        $views = [];
        $viewNamespace = $context['view_namespace'];
        $routePrefix = $context['route_name_prefix'];
        $ajaxEnabled = $context['enable_ajax'];
        $importExportEnabled = $context['enable_import_export'];

        $views[$viewNamespace.'/layouts/app.blade.php'] = $this->resolveLayoutTemplate($context);

        foreach ($schema['tables'] ?? [] as $table) {
            $modelName = $this->modelName($table['name']);
            $resourceName = Str::snake(Str::pluralStudly($modelName));
            $resourceVariable = Str::camel($modelName);
            $resourceCollection = Str::camel(Str::pluralStudly($modelName));
            $fields = $this->buildFieldMeta($table, $schema['tables'] ?? []);

            $views[$viewNamespace.'/'.$resourceName.'/index.blade.php'] = $this->indexView(
                $modelName,
                $resourceName,
                $resourceCollection,
                $fields,
                $viewNamespace,
                $routePrefix,
                $ajaxEnabled,
                $importExportEnabled
            );

            $views[$viewNamespace.'/'.$resourceName.'/create.blade.php'] = $this->createView($modelName, $resourceName, $viewNamespace, $routePrefix, $ajaxEnabled);
            $views[$viewNamespace.'/'.$resourceName.'/show.blade.php'] = $this->showView($modelName, $resourceName, $resourceVariable, $fields, $viewNamespace, $routePrefix);
            $views[$viewNamespace.'/'.$resourceName.'/edit.blade.php'] = $this->editView($modelName, $resourceName, $resourceVariable, $viewNamespace, $routePrefix, $ajaxEnabled);
            $views[$viewNamespace.'/'.$resourceName.'/_form.blade.php'] = $this->formPartial($fields, $resourceVariable);
        }

        return $views;
    }

    private function resolveLayoutTemplate(array $context): string
    {
        $mode = $context['template_mode'];
        $custom = trim((string) ($context['template_content'] ?? ''));

        if (in_array($mode, ['custom_code', 'uploaded_file'], true) && $custom !== '') {
            if (str_contains($custom, '{{content}}')) {
                return str_replace('{{content}}', "@yield('content')", $custom);
            }

            return $custom."\n<div class=\"container\">@yield('content')</div>\n";
        }

        return $this->defaultLayout($context['module_name']);
    }

    private function defaultLayout(string $moduleName): string
    {
        return <<<BLADE
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{$moduleName} Module</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7fb; color: #111827; }
        .container { max-width: 1100px; margin: 24px auto; padding: 0 16px; }
        .card { background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; }
        .table { width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 10px; text-align: left; vertical-align: top; }
        .btn { display: inline-block; border: none; border-radius: 6px; padding: 8px 12px; color: #fff; text-decoration: none; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #2563eb; } .btn-warning { background: #d97706; } .btn-danger { background: #dc2626; } .btn-secondary { background: #4b5563; }
        .btn-sm { padding: 6px 10px; font-size: 13px; }
        .form-control { width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 600; }
        .mb-3 { margin-bottom: 14px; } .text-danger { color: #b91c1c; margin-top: 6px; font-size: 13px; }
        .alert { border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; }
        .alert-success { background: #ecfdf3; border: 1px solid #86efac; color: #14532d; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 12px; align-items: center; }
    </style>
</head>
<body>
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="card">
            @yield('content')
        </div>
    </div>
</body>
@stack('scripts')
</html>
BLADE;
    }

    private function indexView(
        string $modelName,
        string $resourceName,
        string $collectionVariable,
        array $fields,
        string $viewNamespace,
        string $routePrefix,
        bool $ajaxEnabled,
        bool $importExportEnabled
    ): string {
        $headers = ['ID'];
        foreach ($fields as $field) {
            $headers[] = Str::headline($field['name']);
        }
        $headerCells = implode("\n", array_map(fn (string $header): string => '                <th>'.$header.'</th>', $headers));

        $valueCells = ["                    <td>{{ \$item->getKey() }}</td>"];
        foreach ($fields as $field) {
            $valueCells[] = '                    <td>'.$this->fieldDisplayExpression('$item', $field).'</td>';
        }
        $valuesBlock = implode("\n", $valueCells);

        $importExportBlock = '';
        if ($importExportEnabled) {
            $importExportBlock = <<<BLADE
    <a href="{{ route('{$routePrefix}{$resourceName}.export') }}" class="btn btn-secondary">Export CSV</a>
    <form action="{{ route('{$routePrefix}{$resourceName}.import') }}" method="POST" enctype="multipart/form-data" style="display:inline-block;">
        @csrf
        <input type="file" name="csv_file" required>
        <button type="submit" class="btn btn-secondary">Import CSV</button>
    </form>
BLADE;
        }

        $sortableColumns = array_merge(
            ['created_at' => 'Created At', 'updated_at' => 'Updated At'],
            array_reduce($fields, static function (array $carry, array $field): array {
                $carry[$field['name']] = \Illuminate\Support\Str::headline($field['name']);

                return $carry;
            }, [])
        );
        $sortOptionLines = [];
        foreach ($sortableColumns as $value => $label) {
            $sortOptionLines[] = '            <option value="'.$value.'" {{ request(\'sort\', \'created_at\') === \''.$value.'\' ? \'selected\' : \'\' }}>'.$label.'</option>';
        }
        $sortOptionsBlock = implode("\n", $sortOptionLines);

        $ajaxScript = $ajaxEnabled ? <<<BLADE
@push('scripts')
<script>
document.querySelectorAll('form[data-ajax="delete"]').forEach(function (form) {
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!confirm('Are you sure?')) return;

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: new FormData(form),
        });

        if (response.ok) {
            location.reload();
        } else {
            alert('Delete request failed.');
        }
    });
});
</script>
@endpush
BLADE : '';

        $deleteFormAttr = $ajaxEnabled ? ' data-ajax="delete"' : '';

        return <<<BLADE
@extends('{$viewNamespace}.layouts.app')

@section('content')
<h1>{$modelName} List</h1>
<div class="toolbar">
    <a href="{{ route('{$routePrefix}{$resourceName}.create') }}" class="btn btn-primary">Create {$modelName}</a>
{$importExportBlock}
</div>
<form method="GET" action="{{ route('{$routePrefix}{$resourceName}.index') }}" class="toolbar" style="margin-top: 8px;">
    <input type="text" name="search" class="form-control" style="max-width: 320px;" placeholder="Search..." value="{{ request('search', '') }}">
    <select name="sort" class="form-control" style="max-width: 220px;">
{$sortOptionsBlock}
    </select>
    <select name="direction" class="form-control" style="max-width: 140px;">
        <option value="desc" {{ request('direction', 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
        <option value="asc" {{ request('direction', 'desc') === 'asc' ? 'selected' : '' }}>Asc</option>
    </select>
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a href="{{ route('{$routePrefix}{$resourceName}.index') }}" class="btn btn-secondary">Reset</a>
</form>
<table class="table" style="margin-top: 16px;">
    <thead>
        <tr>
{$headerCells}
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse(\${$collectionVariable} as \$item)
            <tr>
{$valuesBlock}
                <td>
                    <a href="{{ route('{$routePrefix}{$resourceName}.show', ['id' => \$item->getKey()]) }}" class="btn btn-sm btn-primary">View</a>
                    <a href="{{ route('{$routePrefix}{$resourceName}.edit', ['id' => \$item->getKey()]) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('{$routePrefix}{$resourceName}.destroy', ['id' => \$item->getKey()]) }}" method="POST" style="display:inline;"{$deleteFormAttr}>
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="99">No data found.</td></tr>
        @endforelse
    </tbody>
</table>
{{ \${$collectionVariable}->links() }}
{$ajaxScript}
@endsection
BLADE;
    }

    private function createView(string $modelName, string $resourceName, string $viewNamespace, string $routePrefix, bool $ajaxEnabled): string
    {
        $attr = $ajaxEnabled ? ' data-ajax="submit"' : '';
        $script = $ajaxEnabled ? $this->ajaxFormScript('create') : '';

        return <<<BLADE
@extends('{$viewNamespace}.layouts.app')

@section('content')
<h1>Create {$modelName}</h1>
<form action="{{ route('{$routePrefix}{$resourceName}.store') }}" method="POST"{$attr}>
    @csrf
    @include('{$viewNamespace}.{$resourceName}._form')
    <button type="submit" class="btn btn-primary">Save</button>
</form>
{$script}
@endsection
BLADE;
    }

    private function showView(string $modelName, string $resourceName, string $resourceVariable, array $fields, string $viewNamespace, string $routePrefix): string
    {
        $rows = [];
        foreach ($fields as $field) {
            $label = Str::headline($field['name']);
            $rows[] = "    <tr><th>{$label}</th><td>".$this->fieldDisplayExpression('$'.$resourceVariable, $field).'</td></tr>';
        }
        $rowsBlock = implode("\n", $rows);

        return <<<BLADE
@extends('{$viewNamespace}.layouts.app')

@section('content')
<h1>{$modelName} Details</h1>
<table class="table">
{$rowsBlock}
</table>
<a href="{{ route('{$routePrefix}{$resourceName}.index') }}" class="btn btn-primary">Back</a>
@endsection
BLADE;
    }

    private function editView(string $modelName, string $resourceName, string $resourceVariable, string $viewNamespace, string $routePrefix, bool $ajaxEnabled): string
    {
        $attr = $ajaxEnabled ? ' data-ajax="submit"' : '';
        $script = $ajaxEnabled ? $this->ajaxFormScript('edit') : '';

        return <<<BLADE
@extends('{$viewNamespace}.layouts.app')

@section('content')
<h1>Edit {$modelName}</h1>
<form action="{{ route('{$routePrefix}{$resourceName}.update', ['id' => \${$resourceVariable}->getKey()]) }}" method="POST"{$attr}>
    @csrf
    @method('PUT')
    @include('{$viewNamespace}.{$resourceName}._form', ['{$resourceVariable}' => \${$resourceVariable}])
    <button type="submit" class="btn btn-primary">Update</button>
</form>
{$script}
@endsection
BLADE;
    }

    private function ajaxFormScript(string $mode): string
    {
        return <<<BLADE
@push('scripts')
<script>
document.querySelectorAll('form[data-ajax="submit"]').forEach(function (form) {
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: new FormData(form),
        });

        if (response.ok) {
            alert('{$mode} success');
            window.location.href = document.referrer || '/';
        } else {
            const data = await response.json().catch(function () { return {}; });
            alert(data.message || 'Request failed.');
        }
    });
});
</script>
@endpush
BLADE;
    }

    private function formPartial(array $fields, string $resourceVariable): string
    {
        if (empty($fields)) {
            return '<p>No editable fields detected.</p>';
        }

        $inputs = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $label = Str::headline($name);
            $fieldType = $field['field_type'] ?? 'text';
            $inputType = $field['input_type'] ?? 'text';
            $valueExpression = "{{ old('{$name}', \${$resourceVariable}->{$name} ?? '') }}";

            if ($fieldType === 'foreign') {
                $optionsVariable = $field['options_variable'] ?? Str::camel(preg_replace('/_id$/', '', $name) ?: $name).'Options';
                $ownerKey = $field['owner_key'] ?? 'id';
                $displayColumn = $field['display_column'] ?? 'name';
                $inputs[] = <<<BLADE
<div class="mb-3">
    <label for="{$name}" class="form-label">{$label}</label>
    <select id="{$name}" name="{$name}" class="form-control">
        <option value="">Select {$label}</option>
        @foreach(\${$optionsVariable} ?? [] as \$option)
            <option value="{{ \$option->{$ownerKey} }}" {{ (string) old('{$name}', \${$resourceVariable}->{$name} ?? '') === (string) \$option->{$ownerKey} ? 'selected' : '' }}>
                {{ \$option->{$displayColumn} ?? \$option->{$ownerKey} }}
            </option>
        @endforeach
    </select>
    @error('{$name}') <div class="text-danger">{{ \$message }}</div> @enderror
</div>
BLADE;
                continue;
            }

            if ($fieldType === 'enum') {
                $enumOptions = $field['enum_options'] ?? [];
                $optionLines = [];
                foreach ($enumOptions as $option) {
                    $escapedOption = addslashes($option);
                    $optionLines[] = "        <option value=\"{$escapedOption}\" {{ old('{$name}', \${$resourceVariable}->{$name} ?? '') === '{$escapedOption}' ? 'selected' : '' }}>{$option}</option>";
                }
                $optionsBlock = implode("\n", $optionLines);
                $inputs[] = <<<BLADE
<div class="mb-3">
    <label for="{$name}" class="form-label">{$label}</label>
    <select id="{$name}" name="{$name}" class="form-control">
{$optionsBlock}
    </select>
    @error('{$name}') <div class="text-danger">{{ \$message }}</div> @enderror
</div>
BLADE;
                continue;
            }

            if ($fieldType === 'boolean') {
                $inputs[] = <<<BLADE
<div class="mb-3">
    <label for="{$name}" class="form-label">{$label}</label>
    <input type="hidden" name="{$name}" value="0">
    <input type="checkbox" id="{$name}" name="{$name}" value="1" {{ (bool) old('{$name}', \${$resourceVariable}->{$name} ?? false) ? 'checked' : '' }}>
    @error('{$name}') <div class="text-danger">{{ \$message }}</div> @enderror
</div>
BLADE;
                continue;
            }

            if ($inputType === 'textarea') {
                $inputs[] = <<<BLADE
<div class="mb-3">
    <label for="{$name}" class="form-label">{$label}</label>
    <textarea id="{$name}" name="{$name}" class="form-control">{$valueExpression}</textarea>
    @error('{$name}') <div class="text-danger">{{ \$message }}</div> @enderror
</div>
BLADE;
                continue;
            }

            $inputs[] = <<<BLADE
<div class="mb-3">
    <label for="{$name}" class="form-label">{$label}</label>
    <input type="{$inputType}" id="{$name}" name="{$name}" value="{$valueExpression}" class="form-control">
    @error('{$name}') <div class="text-danger">{{ \$message }}</div> @enderror
</div>
BLADE;
        }

        return implode("\n\n", $inputs);
    }

    private function buildFieldMeta(array $table, array $allTables): array
    {
        $primaryKeys = $table['primary_keys'] ?? [];
        $fields = [];
        $foreignMeta = [];
        foreach ($table['foreign_keys'] ?? [] as $foreignKey) {
            $column = (string) ($foreignKey['column'] ?? '');
            if ($column === '') {
                continue;
            }

            $foreignMeta[$column] = [
                'owner_key' => (string) ($foreignKey['references_column'] ?? 'id'),
                'display_column' => $this->resolveDisplayColumn((string) ($foreignKey['references_table'] ?? ''), $allTables),
                'options_variable' => Str::camel(preg_replace('/_id$/', '', $column) ?: $column).'Options',
                'relation_method' => Str::camel(Str::singular(preg_replace('/_id$/', '', $column) ?: $column)),
            ];
        }

        foreach ($table['columns'] ?? [] as $column) {
            $name = (string) ($column['name'] ?? '');
            if ($name === '' || !Str::endsWith($name, '_id') || isset($foreignMeta[$name])) {
                continue;
            }

            $resolvedTable = $this->resolveReferenceTableName($name, (string) ($table['name'] ?? ''), $allTables);
            if ($resolvedTable === null) {
                continue;
            }

            $foreignMeta[$name] = [
                'owner_key' => 'id',
                'display_column' => $this->resolveDisplayColumn($resolvedTable, $allTables),
                'options_variable' => Str::camel(preg_replace('/_id$/', '', $name) ?: $name).'Options',
                'relation_method' => Str::camel(Str::singular(preg_replace('/_id$/', '', $name) ?: $name)),
            ];
        }

        foreach ($table['columns'] ?? [] as $column) {
            $name = $column['name'];
            $type = strtolower((string) ($column['type'] ?? ''));
            $rawType = strtolower((string) ($column['raw_type'] ?? ''));
            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            if (in_array($name, $primaryKeys, true) && ($column['auto_increment'] ?? false)) {
                continue;
            }

            $fields[] = [
                'name' => $name,
                'field_type' => $this->fieldTypeForColumn($name, $type, $rawType, isset($foreignMeta[$name])),
                'input_type' => $this->inputTypeForColumn($name, $type, $rawType),
                'enum_options' => $this->extractEnumValues($rawType),
                'options_variable' => $foreignMeta[$name]['options_variable'] ?? null,
                'owner_key' => $foreignMeta[$name]['owner_key'] ?? 'id',
                'display_column' => $foreignMeta[$name]['display_column'] ?? 'name',
                'relation_method' => $foreignMeta[$name]['relation_method'] ?? null,
            ];
        }

        return $fields;
    }

    private function fieldTypeForColumn(string $name, string $type, string $rawType, bool $isForeign): string
    {
        if ($isForeign) {
            return 'foreign';
        }

        if ($type === 'enum') {
            return 'enum';
        }

        if ($this->isBooleanLikeColumn($name, $type, $rawType)) {
            return 'boolean';
        }

        if (in_array($type, ['text', 'tinytext', 'mediumtext', 'longtext'], true)) {
            return 'textarea';
        }

        return 'text';
    }

    private function inputTypeForColumn(string $name, string $type, string $rawType): string
    {
        if (Str::contains($name, 'email')) {
            return 'email';
        }

        if (Str::endsWith($name, '_id') || in_array($type, ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'decimal', 'float', 'double'], true)) {
            return 'number';
        }

        if (in_array($type, ['date', 'datetime', 'timestamp'], true)) {
            return $type === 'date' ? 'date' : 'datetime-local';
        }

        if (in_array($type, ['text', 'tinytext', 'mediumtext', 'longtext'], true)) {
            return 'textarea';
        }

        if ($this->isBooleanLikeColumn($name, $type, $rawType)) {
            return 'checkbox';
        }

        return 'text';
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

    private function fieldDisplayExpression(string $variableRef, array $field): string
    {
        $fieldName = $field['name'] ?? '';
        if (($field['field_type'] ?? '') === 'foreign' && !empty($field['relation_method'])) {
            $relation = $field['relation_method'];
            $display = $field['display_column'] ?? 'name';

            return "{{ {$variableRef}->{$relation}?->{$display} ?? {$variableRef}->{$fieldName} }}";
        }

        return "{{ {$variableRef}->{$fieldName} }}";
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
