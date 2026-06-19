<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL CRUD Generator Module</title>
    <style>
        :root {
            --bg: #eef3f8;
            --panel: #ffffff;
            --panel-soft: #f8fafc;
            --text: #0f172a;
            --muted: #64748b;
            --border: #dbe3ee;
            --accent: #0f766e;
            --accent-strong: #0b5e57;
            --danger: #b91c1c;
            --ok-bg: #ecfdf5;
            --ok-border: #a7f3d0;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Trebuchet MS", sans-serif;
            background: radial-gradient(circle at top right, #ddeef8 0%, var(--bg) 45%, #e9eef5 100%);
            color: var(--text);
        }
        .container { max-width: 1240px; margin: 28px auto; padding: 0 16px 32px; }
        .hero {
            margin-bottom: 16px;
            padding: 18px 20px;
            border: 1px solid var(--border);
            border-radius: 14px;
            background: linear-gradient(135deg, #ffffff 0%, #f5fbfb 100%);
        }
        .hero h1 { margin: 0 0 8px; font-size: 28px; }
        .hero p { margin: 0; color: var(--muted); line-height: 1.55; }
        .card {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
            margin-bottom: 16px;
            box-shadow: 0 6px 22px rgba(15, 23, 42, 0.06);
        }
        .section-title { margin: 0 0 12px; font-size: 20px; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .input, textarea, select {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
            background: #fff;
            color: var(--text);
        }
        textarea {
            min-height: 220px;
            font-family: Consolas, monospace;
            font-size: 13px;
            resize: vertical;
        }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        .field { margin-top: 12px; }
        .hint { color: var(--muted); font-size: 12px; margin-top: 4px; }
        .error { color: var(--danger); margin-top: 6px; font-size: 13px; }
        .toggle-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 14px; }
        .toggle {
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid var(--border);
            background: var(--panel-soft);
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
        }
        .actions { margin-top: 14px; display: flex; gap: 10px; flex-wrap: wrap; }
        .sub-actions { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
        .btn {
            appearance: none;
            border: none;
            border-radius: 10px;
            padding: 10px 16px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: var(--accent-strong); }
        .btn-soft { background: #e2e8f0; color: #0f172a; }
        .btn-soft:hover { background: #cbd5e1; }
        .builder-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .chip-grid { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
        .chip {
            border: 1px solid var(--border);
            background: #f1f5f9;
            color: #0f172a;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            cursor: pointer;
        }
        .chip.active { background: #ccfbf1; border-color: #5eead4; }
        .ok {
            border: 1px solid var(--ok-border);
            background: var(--ok-bg);
            color: #14532d;
            border-radius: 10px;
            padding: 12px;
            margin-top: 14px;
            line-height: 1.6;
        }
        .list { margin: 0; padding-left: 18px; }
        .list li { margin-bottom: 6px; word-break: break-all; }
        .report-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .report-box { border: 1px solid var(--border); border-radius: 10px; padding: 12px; background: #fff; }
        .report-box h3 { margin: 0 0 8px; font-size: 15px; }
        pre {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            white-space: pre-wrap;
            font-size: 12px;
            line-height: 1.55;
        }
        @media (max-width: 900px) {
            .grid, .toggle-grid, .report-grid, .builder-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="hero">
        <h1>SQL to Laravel CRUD Generator</h1>
        <p>Generate advanced module CRUD directly in project-native folders (Controllers, Models, Requests, Views, Routes, Migrations) with API/Web toggles, AJAX, template options, and import-export support.</p>
    </div>

    <div class="card">
        <h2 class="section-title">Generator Configuration</h2>
        <form method="POST" action="{{ route('sql-crud-generator.generate') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid">
                <div class="field">
                    <label>Module Name</label>
                    <input class="input" type="text" name="module_name" value="{{ old('module_name', $moduleName ?? '') }}" placeholder="Blog">
                    @error('module_name') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Generation Mode</label>
                    @php($selectedGenerationMode = old('generation_mode', $generationMode ?? 'auto'))
                    <select name="generation_mode">
                        <option value="auto" {{ $selectedGenerationMode === 'auto' ? 'selected' : '' }}>Auto (Prefer SQL, fallback Prompt)</option>
                        <option value="sql" {{ $selectedGenerationMode === 'sql' ? 'selected' : '' }}>SQL Only</option>
                        <option value="prompt" {{ $selectedGenerationMode === 'prompt' ? 'selected' : '' }}>Prompt Only</option>
                    </select>
                    @error('generation_mode') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="grid">
                <div class="field">
                    <label>CRUD Mode</label>
                    <select name="crud_mode">
                        @php($selectedMode = old('crud_mode', $crudMode ?? 'web'))
                        <option value="web" {{ $selectedMode === 'web' ? 'selected' : '' }}>Web</option>
                        <option value="api" {{ $selectedMode === 'api' ? 'selected' : '' }}>API</option>
                        <option value="both" {{ $selectedMode === 'both' ? 'selected' : '' }}>Both</option>
                    </select>
                    @error('crud_mode') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="field">
                <label>SQL Schema</label>
                <textarea name="sql_schema" placeholder="Paste CREATE TABLE statements...">{{ old('sql_schema', $sqlSchema ?? '') }}</textarea>
                @error('sql_schema') <div class="error">{{ $message }}</div> @enderror
            </div>

            <div class="field">
                <label>Prompt Specification</label>
                <textarea id="prompt_spec" name="prompt_spec" placeholder="Example:
Table: customers
Field: name (varchar(255))
Field: email (varchar(255))

Table: orders
Field: customer_id (bigint)
Field: total_amount (decimal(10,2))">{{ old('prompt_spec', $promptSpec ?? '') }}</textarea>
                @error('prompt_spec') <div class="error">{{ $message }}</div> @enderror
                <div class="hint">Use when no SQL is available. Prompt parser is fully rule-based (no external AI API).</div>
                <div class="sub-actions">
                    <button class="btn btn-soft" type="button" data-template="ecommerce-mini">Use Ecommerce Template</button>
                    <button class="btn btn-soft" type="button" data-template="subscription-mini">Use Subscription Template</button>
                    <button class="btn btn-soft" type="button" data-template="crm-mini">Use CRM Template</button>
                    <button class="btn btn-soft" type="button" id="clear-prompt">Clear Prompt</button>
                </div>
            </div>

            <div class="card" style="margin-top:14px;">
                <h2 class="section-title">Guided Prompt Builder</h2>
                <div class="builder-grid">
                    <div class="field">
                        <label>Domain</label>
                        <select id="builder-domain">
                            <option value="sales">Sales</option>
                            <option value="subscription">Subscription</option>
                            <option value="inventory">Inventory</option>
                            <option value="crm">CRM</option>
                        </select>
                    </div>
                    <div class="field">
                        <label>Primary Entity (singular)</label>
                        <input class="input" id="builder-entity" type="text" placeholder="order">
                    </div>
                    <div class="field">
                        <label>Related Entities (comma separated)</label>
                        <input class="input" id="builder-relations" type="text" placeholder="customer, product">
                    </div>
                </div>
                <div class="field">
                    <label>Common Fields</label>
                    <div class="chip-grid">
                        <button class="chip" type="button" data-field="name:varchar(255)">name</button>
                        <button class="chip" type="button" data-field="title:varchar(255)">title</button>
                        <button class="chip" type="button" data-field="description:text">description</button>
                        <button class="chip" type="button" data-field="status:varchar(50)">status</button>
                        <button class="chip" type="button" data-field="price:decimal(10,2)">price</button>
                        <button class="chip" type="button" data-field="quantity:integer">quantity</button>
                        <button class="chip" type="button" data-field="is_active:boolean">is_active</button>
                        <button class="chip" type="button" data-field="start_date:date">start_date</button>
                    </div>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="button" id="build-prompt">Build Prompt Spec</button>
                </div>
            </div>

            <div class="grid">
                <div class="field">
                    <label>Template Mode</label>
                    @php($selectedTemplate = old('template_mode', $templateMode ?? 'default'))
                    <select name="template_mode">
                        <option value="default" {{ $selectedTemplate === 'default' ? 'selected' : '' }}>Default Theme</option>
                        <option value="custom_code" {{ $selectedTemplate === 'custom_code' ? 'selected' : '' }}>Paste Custom Layout Code</option>
                        <option value="uploaded_file" {{ $selectedTemplate === 'uploaded_file' ? 'selected' : '' }}>Upload Template File</option>
                    </select>
                    @error('template_mode') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field">
                    <label>Notification Email (Optional)</label>
                    <input class="input" type="email" name="notification_email" value="{{ old('notification_email', $notificationEmail ?? '') }}" placeholder="dev@example.com">
                    @error('notification_email') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="field">
            <label>Custom Template Code</label>
            <textarea name="template_code" placeholder="Paste full HTML layout. Use @{{content}} where CRUD content should render.">{{ old('template_code', $templateCode ?? '') }}</textarea>
            @error('template_code') <div class="error">{{ $message }}</div> @enderror
            <div class="hint">Works when Template Mode = Paste Custom Layout Code.</div>
            </div>

            <div class="grid">
                <div class="field">
                    <label>Template File Upload</label>
                    <input class="input" type="file" name="template_file" accept=".html,.blade.php,.txt">
                    @error('template_file') <div class="error">{{ $message }}</div> @enderror
                    <div class="hint">Works when Template Mode = Upload Template File.</div>
                </div>
                <div class="field">
                    <label>Template Screenshot (Reference)</label>
                    <input class="input" type="file" name="template_screenshot" accept="image/*">
                    @error('template_screenshot') <div class="error">{{ $message }}</div> @enderror
                    <div class="hint">Saved for reference; auto-conversion from image to Blade is not deterministic.</div>
                </div>
            </div>

            <div class="toggle-grid">
                <label class="toggle"><input type="checkbox" name="overwrite_existing" value="1" {{ old('overwrite_existing', $defaultOverwrite ?? false) ? 'checked' : '' }}> Overwrite existing module files</label>
                <label class="toggle"><input type="checkbox" name="enable_ajax" value="1" {{ old('enable_ajax', $enableAjax ?? false) ? 'checked' : '' }}> AJAX-ready web CRUD</label>
                <label class="toggle"><input type="checkbox" name="enable_import_export" value="1" {{ old('enable_import_export', $enableImportExport ?? false) ? 'checked' : '' }}> Import/Export (CSV)</label>
                <label class="toggle"><input type="checkbox" name="enable_email_notifications" value="1" {{ old('enable_email_notifications', $enableEmailNotifications ?? false) ? 'checked' : '' }}> Email notifications on create/update</label>
                <label class="toggle"><input type="checkbox" name="generate_migrations" value="1" {{ old('generate_migrations', $generateMigrations ?? true) ? 'checked' : '' }}> Generate Migrations</label>
                <label class="toggle"><input type="checkbox" name="auto_run_migrations" value="1" {{ old('auto_run_migrations', $autoRunMigrations ?? false) ? 'checked' : '' }}> Auto Run Migrations</label>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Generate Advanced Module</button>
            </div>
        </form>

        @if(isset($writeReport))
            <div class="ok">
                Controllers path: <strong>{{ $context['controllers_path'] ?? '' }}</strong><br>
                Models path: <strong>{{ $context['models_path'] ?? '' }}</strong><br>
                Requests path: <strong>{{ $context['requests_path'] ?? '' }}</strong><br>
                Views path: <strong>{{ $context['views_path'] ?? '' }}</strong><br>
                Created: {{ count($writeReport['created'] ?? []) }},
                Updated: {{ count($writeReport['updated'] ?? []) }},
                Skipped: {{ count($writeReport['skipped'] ?? []) }},
                Errors: {{ count($writeReport['errors'] ?? []) }}
                @if(!empty($writeReport['migrations_ran']))
                    <br>Migrations executed: <strong>Yes</strong>
                @endif
            </div>
        @endif
    </div>

    @if(isset($writeReport))
        <div class="card">
            <h2 class="section-title">Write Report</h2>
            <div class="report-grid">
                <div class="report-box">
                    <h3>Created</h3>
                    @if(empty($writeReport['created'])) <p>No files created.</p> @else
                        <ul class="list">@foreach($writeReport['created'] as $file)<li>{{ $file }}</li>@endforeach</ul>
                    @endif
                </div>
                <div class="report-box">
                    <h3>Updated</h3>
                    @if(empty($writeReport['updated'])) <p>No files updated.</p> @else
                        <ul class="list">@foreach($writeReport['updated'] as $file)<li>{{ $file }}</li>@endforeach</ul>
                    @endif
                </div>
                <div class="report-box">
                    <h3>Skipped</h3>
                    @if(empty($writeReport['skipped'])) <p>No files skipped.</p> @else
                        <ul class="list">@foreach($writeReport['skipped'] as $file)<li>{{ $file }}</li>@endforeach</ul>
                    @endif
                </div>
                <div class="report-box">
                    <h3>Errors</h3>
                    @if(empty($writeReport['errors'])) <p>No errors.</p> @else
                        <ul class="list">@foreach($writeReport['errors'] as $error)<li>{{ $error }}</li>@endforeach</ul>
                    @endif
                </div>
            </div>

            <div class="report-box" style="margin-top: 14px;">
                <h3>Provider Registration</h3>
                <p>Added: {{ $writeReport['provider_added'] ?? 'No' }}</p>
                <p>Skipped: {{ $writeReport['provider_skipped'] ?? 'No' }}</p>
            </div>

            @if(!empty($writeReport['migration_output']))
                <h3 style="margin-top:14px;">Migration Output</h3>
                <pre>{{ $writeReport['migration_output'] }}</pre>
            @endif
        </div>

        <div class="card">
            <h2 class="section-title">Parsed Schema</h2>
            <pre>{{ json_encode($output['parsed_schema'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif
</div>
<script>
    (function () {
        const promptEl = document.getElementById('prompt_spec');
        if (!promptEl) {
            return;
        }

        const templates = {
            'ecommerce-mini': `Table: customers
Field: name (varchar(255))
Field: email (varchar(255))
Field: phone (varchar(50))
Field: is_active (boolean)

Table: products
Field: name (varchar(255))
Field: sku (varchar(100))
Field: price (decimal(10,2))
Field: stock (integer)

Table: orders
Field: customer_id (bigint)
Field: order_date (date)
Field: total_amount (decimal(10,2))
Field: status (varchar(50))`,
            'subscription-mini': `Table: plans
Field: name (varchar(255))
Field: code (varchar(100))
Field: price (decimal(10,2))
Field: billing_cycle (varchar(50))
Field: is_active (boolean)

Table: subscribers
Field: name (varchar(255))
Field: email (varchar(255))
Field: phone (varchar(50))

Table: subscriptions
Field: subscriber_id (bigint)
Field: plan_id (bigint)
Field: starts_at (date)
Field: ends_at (date)
Field: status (varchar(50))`,
            'crm-mini': `Table: leads
Field: name (varchar(255))
Field: email (varchar(255))
Field: phone (varchar(50))
Field: source (varchar(100))
Field: status (varchar(50))

Table: deals
Field: lead_id (bigint)
Field: title (varchar(255))
Field: value (decimal(10,2))
Field: stage (varchar(50))

Table: activities
Field: lead_id (bigint)
Field: note (text)
Field: activity_date (date)`
        };

        document.querySelectorAll('[data-template]').forEach((btn) => {
            btn.addEventListener('click', function () {
                const key = this.getAttribute('data-template');
                if (templates[key]) {
                    promptEl.value = templates[key];
                }
            });
        });

        const clearBtn = document.getElementById('clear-prompt');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                promptEl.value = '';
            });
        }

        const chips = document.querySelectorAll('.chip[data-field]');
        chips.forEach((chip) => {
            chip.addEventListener('click', function () {
                this.classList.toggle('active');
            });
        });

        const buildBtn = document.getElementById('build-prompt');
        if (buildBtn) {
            buildBtn.addEventListener('click', function () {
                const entity = (document.getElementById('builder-entity').value || 'record').trim().toLowerCase();
                const entityTable = entity.endsWith('s') ? entity : entity + 's';
                const relationsRaw = (document.getElementById('builder-relations').value || '').trim();
                const selectedFields = [];

                chips.forEach((chip) => {
                    if (chip.classList.contains('active')) {
                        selectedFields.push(chip.getAttribute('data-field'));
                    }
                });

                const relationTables = relationsRaw === '' ? [] : relationsRaw.split(',').map(v => v.trim().toLowerCase()).filter(Boolean);
                let spec = `Table: ${entityTable}\n`;

                relationTables.forEach((rel) => {
                    const relName = rel.endsWith('s') ? rel.slice(0, -1) : rel;
                    spec += `Field: ${relName}_id (bigint)\n`;
                });

                if (selectedFields.length === 0) {
                    spec += `Field: name (varchar(255))\nField: status (varchar(50))\n`;
                } else {
                    selectedFields.forEach((field) => {
                        const parts = field.split(':');
                        if (parts.length === 2) {
                            spec += `Field: ${parts[0]} (${parts[1]})\n`;
                        }
                    });
                }

                relationTables.forEach((rel) => {
                    const relTable = rel.endsWith('s') ? rel : rel + 's';
                    spec += `\nTable: ${relTable}\nField: name (varchar(255))\n`;
                });

                promptEl.value = spec.trim();
            });
        }
    })();
</script>
</body>
</html>
