<?php

namespace SqlCrudGenerator\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use SqlCrudGenerator\Services\GeneratedModuleWriterService;
use SqlCrudGenerator\Services\ModuleCrudGeneratorService;
use SqlCrudGenerator\Services\PromptSchemaService;
use SqlCrudGenerator\Services\SqlParserService;

class GeneratorController extends Controller
{
    public function __construct(
        private readonly SqlParserService $sqlParserService,
        private readonly PromptSchemaService $promptSchemaService,
        private readonly ModuleCrudGeneratorService $moduleCrudGeneratorService,
        private readonly GeneratedModuleWriterService $generatedModuleWriterService
    ) {
    }

    public function index()
    {
        return view('sql-crud-generator::index', [
            'defaultOverwrite' => (bool) config('sql-crud-generator.default_overwrite', false),
            'crudMode' => 'web',
            'generationMode' => 'auto',
            'promptSpec' => '',
            'templateMode' => 'default',
            'templateCode' => '',
            'notificationEmail' => '',
            'enableAjax' => false,
            'enableImportExport' => false,
            'enableEmailNotifications' => false,
            'generateMigrations' => true,
            'autoRunMigrations' => false,
        ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'module_name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/'],
            'generation_mode' => ['required', 'in:sql,prompt,auto'],
            'sql_schema' => ['nullable', 'string'],
            'prompt_spec' => ['nullable', 'string'],
            'crud_mode' => ['required', 'in:web,api,both'],
            'template_mode' => ['required', 'in:default,custom_code,uploaded_file'],
            'template_code' => ['nullable', 'string'],
            'template_file' => ['nullable', 'file', 'max:5120'],
            'template_screenshot' => ['nullable', 'image', 'max:5120'],
            'notification_email' => ['nullable', 'email', 'max:255'],
        ]);

        $generationMode = $validated['generation_mode'];
        $sqlSchema = trim((string) ($validated['sql_schema'] ?? ''));
        $promptSpec = trim((string) ($validated['prompt_spec'] ?? ''));

        if ($generationMode === 'sql' && $sqlSchema === '') {
            return back()->withErrors(['sql_schema' => 'SQL schema is required in SQL mode.'])->withInput();
        }

        if ($generationMode === 'prompt' && $promptSpec === '') {
            return back()->withErrors(['prompt_spec' => 'Prompt specification is required in Prompt mode.'])->withInput();
        }

        if ($generationMode === 'auto' && $sqlSchema === '' && $promptSpec === '') {
            return back()->withErrors(['sql_schema' => 'Provide SQL schema or prompt specification in Auto mode.'])->withInput();
        }

        $templateContent = $this->resolveTemplateContent($request, $validated);
        $context = $this->moduleCrudGeneratorService->buildContext($validated['module_name'], [
            'crud_mode' => $validated['crud_mode'],
            'enable_ajax' => $request->boolean('enable_ajax'),
            'enable_import_export' => $request->boolean('enable_import_export'),
            'enable_email_notifications' => $request->boolean('enable_email_notifications'),
            'notification_email' => $validated['notification_email'] ?? '',
            'template_mode' => $validated['template_mode'],
            'template_content' => $templateContent['content'],
            'template_source_name' => $templateContent['template_source_name'],
            'screenshot_source_name' => $templateContent['screenshot_source_name'],
            'generate_migrations' => $request->boolean('generate_migrations', true),
            'auto_run_migrations' => $request->boolean('auto_run_migrations', false),
        ]);

        $parsedSchema = $this->resolveParsedSchema($generationMode, $sqlSchema, $promptSpec, $validated['module_name']);
        $output = $this->moduleCrudGeneratorService->generate($parsedSchema, $context);
        $overwrite = $request->boolean('overwrite_existing', (bool) config('sql-crud-generator.default_overwrite', false));
        $writeReport = $this->generatedModuleWriterService->write($output, $context, $overwrite);
        $assetReport = $this->storeReferenceAssets($request, $context, $overwrite);
        $writeReport['created'] = array_merge($writeReport['created'], $assetReport['created']);
        $writeReport['updated'] = array_merge($writeReport['updated'], $assetReport['updated']);
        $writeReport['skipped'] = array_merge($writeReport['skipped'], $assetReport['skipped']);
        $writeReport['errors'] = array_merge($writeReport['errors'], $assetReport['errors']);

        return view('sql-crud-generator::index', [
            'moduleName' => $validated['module_name'],
            'sqlSchema' => $sqlSchema,
            'promptSpec' => $promptSpec,
            'generationMode' => $generationMode,
            'output' => $output,
            'context' => $context,
            'writeReport' => $writeReport,
            'defaultOverwrite' => $overwrite,
            'crudMode' => $validated['crud_mode'],
            'templateMode' => $validated['template_mode'],
            'templateCode' => $validated['template_code'] ?? '',
            'notificationEmail' => $validated['notification_email'] ?? '',
            'enableAjax' => $request->boolean('enable_ajax'),
            'enableImportExport' => $request->boolean('enable_import_export'),
            'enableEmailNotifications' => $request->boolean('enable_email_notifications'),
            'generateMigrations' => $request->boolean('generate_migrations', true),
            'autoRunMigrations' => $request->boolean('auto_run_migrations', false),
        ]);
    }

    private function resolveParsedSchema(string $generationMode, string $sqlSchema, string $promptSpec, string $moduleName): array
    {
        if ($generationMode === 'sql') {
            return $this->sqlParserService->parse($sqlSchema);
        }

        if ($generationMode === 'prompt') {
            return $this->promptSchemaService->parse($promptSpec, $moduleName);
        }

        if ($sqlSchema !== '') {
            return $this->sqlParserService->parse($sqlSchema);
        }

        return $this->promptSchemaService->parse($promptSpec, $moduleName);
    }

    private function resolveTemplateContent(Request $request, array $validated): array
    {
        $mode = $validated['template_mode'];
        $content = '';
        $templateSourceName = '';
        $screenshotSourceName = '';

        if ($mode === 'custom_code') {
            $content = (string) ($validated['template_code'] ?? '');
        }

        if ($mode === 'uploaded_file') {
            /** @var UploadedFile|null $templateFile */
            $templateFile = $request->file('template_file');
            if ($templateFile instanceof UploadedFile) {
                $templateSourceName = $templateFile->getClientOriginalName();
                $content = (string) file_get_contents($templateFile->getRealPath());
            }
        }

        /** @var UploadedFile|null $screenshot */
        $screenshot = $request->file('template_screenshot');
        if ($screenshot instanceof UploadedFile) {
            $screenshotSourceName = $screenshot->getClientOriginalName();
        }

        return [
            'content' => $content,
            'template_source_name' => $templateSourceName,
            'screenshot_source_name' => $screenshotSourceName,
        ];
    }

    private function storeReferenceAssets(Request $request, array $context, bool $overwrite): array
    {
        $report = ['created' => [], 'updated' => [], 'skipped' => [], 'errors' => []];
        $assetsPath = $context['module_reference_path'];

        if (!is_dir($assetsPath) && !mkdir($assetsPath, 0755, true) && !is_dir($assetsPath)) {
            $report['errors'][] = 'Unable to create reference template directory: '.$assetsPath;
            return $report;
        }

        $this->storeUploadedFile($request->file('template_file'), $assetsPath, 'template_source', $overwrite, $report);
        $this->storeUploadedFile($request->file('template_screenshot'), $assetsPath, 'template_screenshot', $overwrite, $report);

        return $report;                                                                                     
    }

    private function storeUploadedFile(?UploadedFile $file, string $directory, string $prefix, bool $overwrite, array &$report): void
    {
        if (!$file instanceof UploadedFile) {
            return;
        }

        $extension = $file->getClientOriginalExtension();
        $fileName = $prefix.($extension !== '' ? '.'.$extension : '');
        $targetPath = $directory.DIRECTORY_SEPARATOR.$fileName;
        $exists = is_file($targetPath);

        if ($exists && !$overwrite) {
            $report['skipped'][] = $targetPath;
            return;
        }

        $sourcePath = $file->getRealPath();
        if ($sourcePath === false || !@copy($sourcePath, $targetPath)) {
            $report['errors'][] = 'Unable to store uploaded reference file: '.$fileName;
            return;
        }

        $report[$exists ? 'updated' : 'created'][] = $targetPath;
    }
}
