# SQL CRUD Generator Module Guide

## 1. Overview

`SQL CRUD Generator` is a reusable Laravel module/package that lets you:

- Paste SQL `CREATE TABLE` schema from browser UI
- Parse table metadata (columns, primary keys, foreign keys)
- Generate CRUD as a **module** inside target Laravel project
- Select generation mode:
  - Web CRUD
  - API CRUD
  - Both Web + API
- Enable optional:
  - AJAX-ready web forms/list actions
  - CSV import/export
  - Email notifications
  - Migration generation
  - Auto-run migration
  - Custom frontend template (paste code/upload file)
  - Screenshot reference upload

Generated code location (project-native):

- `app/Http/Controllers/{ModuleName}`
- `app/Http/Controllers/{ModuleName}/Api` (if API mode enabled)
- `app/Models/{ModuleName}`
- `app/Http/Requests/{ModuleName}`
- `resources/views/{module-kebab}`

UI URL:

- `/sql-crud-generator`

---

## 2. What Gets Generated

For each SQL table, module generator creates:

- Model
- Resource Controller (index, create, store, show, edit, update, destroy)
- Form Requests (Store/Update validation)
- Blade views (`index`, `create`, `show`, `edit`, `_form`)
- Route snippets appended into project routes:
  - `routes/web.php`
  - `routes/api.php` (if API mode enabled)
- Migration files generated into:
  - `database/migrations`

Final structure example:

```text
app/Http/Controllers/Library/
app/Http/Controllers/Library/Api/
app/Models/Library/
app/Http/Requests/Library/
resources/views/library/
routes/web.php
routes/api.php
database/migrations/
```

---

## 3. Current Package Location In This Project

Package source is here:

- `packages/SqlCrudGenerator`

Important files:

- `packages/SqlCrudGenerator/src/SqlCrudGeneratorServiceProvider.php`
- `packages/SqlCrudGenerator/routes/web.php`
- `packages/SqlCrudGenerator/src/Http/Controllers/GeneratorController.php`
- `packages/SqlCrudGenerator/src/Services/*`

---

## 4. Use In This Project

1. Start Laravel server:

```bash
php artisan serve
```

2. Open:

```text
http://127.0.0.1:8000/sql-crud-generator
```

3. Enter:
- Module name (example: `Blog`)
- SQL schema
- Choose `CRUD Mode` (`web` / `api` / `both`)
- Select template mode (`default`, `custom code`, `uploaded file`)
- Optional flags:
  - AJAX
  - Import/Export
  - Email notification

4. Click **Generate Module**

5. Generated files will be created directly in project folders:

```text
app/Http/Controllers/{ModuleName}
app/Models/{ModuleName}
app/Http/Requests/{ModuleName}
resources/views/{module-kebab}
routes/web.php (+ routes/api.php for API mode)
```

---

## 5. Use In Any Other Laravel Project (Proper Steps)

Follow these exact steps in another Laravel project.

### Step 1: Copy Package Folder

Copy this folder into target project root:

```text
packages/SqlCrudGenerator
```

### Step 2: Add PSR-4 Autoload

Edit target project `composer.json`:

```json
"autoload": {
  "psr-4": {
    "App\\": "app/",
    "SqlCrudGenerator\\": "packages/SqlCrudGenerator/src/"
  }
}
```

### Step 3: Register Package Provider

Edit `bootstrap/providers.php` and add:

```php
SqlCrudGenerator\SqlCrudGeneratorServiceProvider::class,
```

Example:

```php
return [
    App\Providers\AppServiceProvider::class,
    SqlCrudGenerator\SqlCrudGeneratorServiceProvider::class,
];
```

### Step 4: Rebuild Autoload + Clear Cache

```bash
composer dump-autoload
php artisan optimize:clear
```

### Step 5: (Optional) Publish Config

```bash
php artisan vendor:publish --tag=sql-crud-generator-config
```

Config file:

- `config/sql-crud-generator.php`

### Step 6: Open Generator UI

```text
http://127.0.0.1:8000/sql-crud-generator
```

Now you can generate modules directly in that project.

---

## 6. Route & Module Naming Behavior

- Route URL prefix = kebab-case module name  
  Example: `BlogManager` -> `/blog-manager/...`
- Route name prefix = kebab-case module name + `.`  
  Example: `blog-manager.users.index`
- API URLs are generated under:
  - `/api/{module-kebab}/...`

---

## 7. Configuration Reference

File: `config/sql-crud-generator.php`

- `route_prefix`: Generator panel URL prefix
- `middleware`: Route middleware for generator panel
- `module_base_path`: legacy compatibility (not primary output location in current native mode)
- `module_namespace`: legacy compatibility (not primary output namespace in current native mode)
- `default_overwrite`: default file overwrite behavior

---

## 8. Notes

- Generator is rule-based PHP logic (no paid API / external AI API).
- Module provider is auto-added in `bootstrap/providers.php` when module is generated.
- Use overwrite carefully for existing modules.
- Screenshot-to-HTML auto-conversion is not deterministic in pure rule-based PHP.
  Screenshot files are stored as design reference in module assets.

---

## 9. Detailed Technical Report (Hinglish)

Ye section aapke interview, client discussion, ya internal handover ke liye detailed explanation deta hai: module ka code kaise work karta hai, kaunsi file kya karti hai, aur generation pipeline ka actual flow kya hai.

### 9.1 Module ka Core Idea

`SQL CRUD Generator` ek Laravel package hai jo:

- SQL schema **ya** prompt input leta hai
- us input ko structured schema me convert karta hai
- phir us schema se Laravel CRUD code generate karta hai
- generated code ko direct project ke native folders me write karta hai

Important: Isme koi external AI API nahi use hoti; pura system deterministic, rule-based PHP logic pe chalta hai.

### 9.2 End-to-End Request Flow

1. User `/sql-crud-generator` page open karta hai  
2. Form submit hota hai (module name, generation mode, sql/prompt, feature toggles)  
3. `GeneratorController` validation karta hai  
4. Parser select hota hai:
   - SQL mode -> `SqlParserService`
   - Prompt mode -> `PromptSchemaService`
   - Auto mode -> SQL prefer, else prompt
5. Parsed schema `ModuleCrudGeneratorService` ko diya jata hai  
6. Alag generator classes model/controller/request/view/route/migration ka code text prepare karti hain  
7. `GeneratedModuleWriterService` files project me write karta hai  
8. UI par write report show hoti hai (`created`, `updated`, `skipped`, `errors`)

### 9.3 File-wise Responsibility Map

#### Entry + UI

- `packages/SqlCrudGenerator/src/Http/Controllers/GeneratorController.php`
  - Form input validate karta hai
  - SQL/Prompt parser choose karta hai
  - Context build kar ke generator run karta hai
  - Final output writer service ko pass karta hai
  - Report view me return karta hai

- `packages/SqlCrudGenerator/resources/views/index.blade.php`
  - Generator panel UI
  - SQL textarea + Prompt specification
  - Generation mode (`sql`, `prompt`, `auto`)
  - Guided prompt builder + prebuilt templates
  - Feature toggles (AJAX, import/export, migrations, etc.)

#### Parsing Layer

- `packages/SqlCrudGenerator/src/Services/SqlParserService.php`
  - `CREATE TABLE` parse karta hai
  - Table name, columns, type, nullable, default nikalta hai
  - Primary key / foreign key detect karta hai
  - Structured array format return karta hai

- `packages/SqlCrudGenerator/src/Services/PromptSchemaService.php`
  - Prompt text ko schema me convert karta hai
  - `Table:` / `Field:` style blocks parse karta hai
  - fallback defaults add karta hai (`id`, `name`, timestamps)
  - `_id` fields se FK inference karta hai

#### Generation Orchestrator

- `packages/SqlCrudGenerator/src/Services/ModuleCrudGeneratorService.php`
  - Central orchestrator
  - Har generator class ko invoke karta hai
  - Combined generated output structure banata hai

#### Code Generators

- `packages/SqlCrudGenerator/src/Services/Generators/ModelGenerator.php`
  - Model class + `$fillable` + `$casts`
  - belongsTo / hasMany relations
  - searchable/sortable metadata

- `packages/SqlCrudGenerator/src/Services/Generators/ControllerGenerator.php`
  - Web CRUD controller methods
  - listing, filters, search, sort
  - import/export integration hooks

- `packages/SqlCrudGenerator/src/Services/Generators/ApiControllerGenerator.php`
  - API CRUD controllers (JSON responses)

- `packages/SqlCrudGenerator/src/Services/Generators/RequestGenerator.php`
  - Store/Update request validation rules
  - name/title/email/_id/type-based rule heuristics

- `packages/SqlCrudGenerator/src/Services/Generators/BladeGenerator.php`
  - `index`, `create`, `show`, `edit`, `_form` views
  - relation-aware form rendering (dropdown/text/checkbox etc.)

- `packages/SqlCrudGenerator/src/Services/Generators/ModuleRouteGenerator.php`
  - web routes generate karta hai

- `packages/SqlCrudGenerator/src/Services/Generators/ApiRouteGenerator.php`
  - api routes generate karta hai

- `packages/SqlCrudGenerator/src/Services/Generators/MigrationGenerator.php`
  - table columns + keys + constraints
  - timestamps + softDeletes support
  - safe `dropIfExists` pattern

#### Write Layer

- `packages/SqlCrudGenerator/src/Services/GeneratedModuleWriterService.php`
  - Generated content ko actual files me write karta hai
  - overwrite/skipped policy apply karta hai
  - optional migration run support
  - detailed write report return karta hai

### 9.4 Generated Files Real Me Kahan Jati Hain

Module generate hone ke baad typical output:

- `app/Http/Controllers/{ModuleName}/...`
- `app/Http/Controllers/{ModuleName}/Api/...` (if enabled)
- `app/Models/{ModuleName}/...`
- `app/Http/Requests/{ModuleName}/...`
- `resources/views/{module-kebab}/...`
- `routes/web.php` updates
- `routes/api.php` updates (if enabled)
- `database/migrations/...`

### 9.5 Recent Critical Fix: created_at / updated_at NULL issue

Issue behavior:
- Record create/edit ho raha tha, lekin table me `created_at` / `updated_at` NULL aa rahe the.

Root cause:
- Migration generator timestamps columns create kar raha tha.
- Lekin model generator kuch scenarios me `$timestamps = false` generate kar raha tha.
- Eloquent auto timestamping off hone ki wajah se values set nahi ho rahi thi.

Fix:
- `ModelGenerator.php` me timestamp logic align kiya gaya.
- Agar generator migrations produce kar raha hai, to model me `$timestamps = false` inject nahi kiya jata.
- Isse migration schema aur model behavior synchronized ho gaye.

Result:
- Newly generated modules me create/update par `created_at` aur `updated_at` auto-populate honge.

### 9.6 Why This Module is “Codex-like” (without LLM API)

Ye module “AI-like developer assistance” deta hai using rule-based automation:

- Input understanding (SQL/prompt)
- schema normalization
- relationship inference
- validation heuristics
- boilerplate code synthesis
- project-native write-back

Difference:
- External LLM jaisa open-ended reasoning nahi
- deterministic predictable generation
- zero API cost, full offline/local compatibility

### 9.7 Practical Best Practice for Team Use

- Har generation se pehle module name + table prefix strategy clear rakho
- Existing large module me overwrite enable karne se pehle backup/branch banao
- Generated code ko initial scaffold samajh kar domain-specific business rules manually add karo
- Prompt mode me structured input do (`Table:` / `Field:` format) for best output quality
