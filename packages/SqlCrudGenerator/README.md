# SQL CRUD Generator (Reusable Laravel Module)

This package provides a web UI that parses SQL `CREATE TABLE` statements and generates CRUD code directly into project-native folders using the chosen module name as a sub-namespace/folder.

## Features

- SQL parser (columns, PK, FK)
- Module-segmented code generation:
  - Models
  - Web Controllers
  - API Controllers
  - Form Requests
  - Blade views
  - Module `web.php` routes
  - Module `api.php` routes
  - Module Service Provider
- Generation modes:
  - `web`
  - `api`
  - `both`
- Optional AJAX-ready web CRUD
- Optional CSV import/export endpoints + buttons
- Optional email notifications on create/update
- SQL-to-migration generation (`database/migrations`)
- Optional auto-run migrations (`php artisan migrate --force`)
- Template support:
  - default layout
  - pasted custom template code (supports `{{content}}` placeholder)
  - uploaded template file
  - screenshot upload saved as reference asset
- Auto-registration of generated module provider in `bootstrap/providers.php`

## URL

`/sql-crud-generator`

## Output Structure

Generated files are written under:

- `app/Http/Controllers/{ModuleName}`
- `app/Http/Controllers/{ModuleName}/Api` (API mode)
- `app/Models/{ModuleName}`
- `app/Http/Requests/{ModuleName}`
- `resources/views/{module-kebab}`
- `resources/views/{module-kebab}/_generator_assets` (uploaded template/screenshot reference files)
- `database/migrations/*_{module}_create_{table}_table.php` (when migration option is enabled)
- Route snippets are appended into:
  - `routes/web.php`
  - `routes/api.php` (created automatically if needed)

## Integrate Into Another Laravel Project

1. Copy folder:

`packages/SqlCrudGenerator`

2. Add autoload namespace in `composer.json`:

```json
"autoload": {
  "psr-4": {
    "SqlCrudGenerator\\": "packages/SqlCrudGenerator/src/"
  }
}
```

3. Add provider in `bootstrap/providers.php`:

```php
SqlCrudGenerator\SqlCrudGeneratorServiceProvider::class,
```

4. Run:

```bash
composer dump-autoload
php artisan optimize:clear
```

5. Open:

`http://127.0.0.1:8000/sql-crud-generator`

## Config (Optional)

Publish config:

```bash
php artisan vendor:publish --tag=sql-crud-generator-config
```

Config file: `config/sql-crud-generator.php`
