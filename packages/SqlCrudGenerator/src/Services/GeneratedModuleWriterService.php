<?php

namespace SqlCrudGenerator\Services;

use Illuminate\Support\Facades\Artisan;

class GeneratedModuleWriterService
{
    public function write(array $generatedOutput, array $context, bool $overwrite = false): array
    {
        $report = [
            'created' => [],
            'updated' => [],
            'skipped' => [],
            'errors' => [],
            'provider_added' => null,
            'provider_skipped' => null,
            'routes_added' => [],
            'routes_skipped' => [],
            'migrations_ran' => false,
            'migration_output' => null,
        ];

        $this->writeFiles($context['models_path'], $generatedOutput['models'] ?? [], $overwrite, $report);
        $this->writeFiles($context['controllers_path'], $generatedOutput['controllers'] ?? [], $overwrite, $report);
        $this->writeFiles($context['requests_path'], $generatedOutput['requests'] ?? [], $overwrite, $report);
        $this->writeFiles(resource_path('views'), $generatedOutput['views'] ?? [], $overwrite, $report);
        $this->writeMigrations(database_path('migrations'), $generatedOutput['migrations'] ?? [], $overwrite, $report);

        $moduleName = (string) ($context['module_name'] ?? '');
        $this->appendRouteSnippet(base_path('routes/web.php'), $generatedOutput['route_snippets_web'] ?? '', $report, $moduleName, 'web');
        $apiSnippet = $generatedOutput['route_snippets_api'] ?? '';
        if (trim($apiSnippet) !== '') {
            $this->ensureApiRouteFileExists($report);
            $this->ensureApiRoutingEnabled($report);
            $this->appendRouteSnippet(base_path('routes/api.php'), $apiSnippet, $report, $moduleName, 'api');
        }

        $this->runMigrationsIfRequested($context, $report);

        return $report;
    }

    private function writeFiles(string $baseDirectory, array $files, bool $overwrite, array &$report): void
    {
        foreach ($files as $relativePath => $content) {
            $targetPath = $baseDirectory.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
            $this->writeFile($targetPath, $content, $overwrite, $report);
        }
    }

    private function writeFile(string $targetPath, string $content, bool $overwrite, array &$report): void
    {
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            $report['errors'][] = 'Failed to create directory: '.$targetDir;
            return;
        }

        $exists = is_file($targetPath);
        if ($exists && !$overwrite) {
            $report['skipped'][] = $targetPath;
            return;
        }

        if (@file_put_contents($targetPath, $content) === false) {
            $report['errors'][] = 'Failed to write file: '.$targetPath;
            return;
        }

        $report[$exists ? 'updated' : 'created'][] = $targetPath;
    }

    private function appendRouteSnippet(string $routeFilePath, string $snippet, array &$report, string $moduleName, string $mode): void
    {
        $snippet = trim($snippet);
        if ($snippet === '') {
            return;
        }

        if (!is_file($routeFilePath)) {
            $report['errors'][] = 'Route file not found: '.$routeFilePath;
            return;
        }

        $existing = file_get_contents($routeFilePath);
        if ($existing === false) {
            $report['errors'][] = 'Unable to read route file: '.$routeFilePath;
            return;
        }

        if ($moduleName !== '') {
            $existing = $this->removeLegacyModuleRouteLines($existing, $moduleName, $mode);
        }

        [$startMarker, $endMarker] = $this->extractBlockMarkers($snippet);
        if ($startMarker !== null && $endMarker !== null) {
            $updated = $this->replaceOrAppendBlock($existing, $snippet, $startMarker, $endMarker);
            if ($updated === $existing) {
                $report['routes_skipped'][] = $startMarker;
                return;
            }

            $report['routes_added'][] = $startMarker;
            if (@file_put_contents($routeFilePath, $updated) === false) {
                $report['errors'][] = 'Failed to update route block in '.$routeFilePath;
            }
            return;
        }

        if (str_contains($existing, $snippet)) {
            $report['routes_skipped'][] = $snippet;
            return;
        }

        $contentToAppend = "\n".$snippet."\n";
        if (@file_put_contents($routeFilePath, $existing.$contentToAppend) === false) {
            $report['errors'][] = 'Failed to append route snippet into '.$routeFilePath;
            return;
        }

        $report['routes_added'][] = $snippet;
    }

    private function ensureApiRouteFileExists(array &$report): void
    {
        $apiPath = base_path('routes/api.php');
        if (is_file($apiPath)) {
            return;
        }

        $content = "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n";
        if (@file_put_contents($apiPath, $content) === false) {
            $report['errors'][] = 'Failed to create routes/api.php';
            return;
        }

        $report['created'][] = $apiPath;
    }

    private function ensureApiRoutingEnabled(array &$report): void
    {
        $bootstrapPath = base_path('bootstrap/app.php');
        if (!is_file($bootstrapPath)) {
            $report['errors'][] = 'bootstrap/app.php not found while enabling API routing.';
            return;
        }

        $content = file_get_contents($bootstrapPath);
        if ($content === false) {
            $report['errors'][] = 'Unable to read bootstrap/app.php';
            return;
        }

        if (str_contains($content, "api: __DIR__.'/../routes/api.php'")) {
            return;
        }

        $updated = preg_replace(
            "/->withRouting\\(\\s*\\n\\s*web:\\s*__DIR__\\.'\\/\\.\\.\\/routes\\/web\\.php',/m",
            "->withRouting(\n        web: __DIR__.'/../routes/web.php',\n        api: __DIR__.'/../routes/api.php',",
            $content,
            1
        );

        if ($updated === null || $updated === $content) {
            $report['errors'][] = 'Failed to inject API routing entry into bootstrap/app.php';
            return;
        }

        if (@file_put_contents($bootstrapPath, $updated) === false) {
            $report['errors'][] = 'Failed to update bootstrap/app.php for API routes';
            return;
        }

        $report['updated'][] = $bootstrapPath;
    }

    private function writeMigrations(string $migrationDirectory, array $migrations, bool $overwrite, array &$report): void
    {
        if (!is_dir($migrationDirectory) && !mkdir($migrationDirectory, 0755, true) && !is_dir($migrationDirectory)) {
            $report['errors'][] = 'Failed to create migrations directory: '.$migrationDirectory;
            return;
        }

        $index = 0;
        foreach ($migrations as $relativePath => $content) {
            $moduleSlug = trim(str_replace('\\', '/', dirname($relativePath)), '/.');
            $baseName = basename($relativePath);

            $existingPattern = $migrationDirectory.DIRECTORY_SEPARATOR.'*_'.$moduleSlug.'_'.$baseName;
            $existingMatches = glob($existingPattern) ?: [];

            if (!empty($existingMatches)) {
                if (!$overwrite) {
                    foreach ($existingMatches as $path) {
                        $report['skipped'][] = $path;
                    }
                    continue;
                }

                $targetPath = $existingMatches[0];
                $this->writeFile($targetPath, $content, true, $report);
                continue;
            }

            $timestamp = date('Y_m_d_His', time() + $index);
            $targetName = $timestamp.'_'.$moduleSlug.'_'.$baseName;
            $targetPath = $migrationDirectory.DIRECTORY_SEPARATOR.$targetName;
            $this->writeFile($targetPath, $content, true, $report);
            $index++;
        }
    }

    private function runMigrationsIfRequested(array $context, array &$report): void
    {
        if (($context['generate_migrations'] ?? true) !== true) {
            return;
        }

        if (($context['auto_run_migrations'] ?? false) !== true) {
            return;
        }

        try {
            Artisan::call('migrate', ['--force' => true]);
            $report['migrations_ran'] = true;
            $report['migration_output'] = Artisan::output();
        } catch (\Throwable $e) {
            $report['errors'][] = 'Migration run failed: '.$e->getMessage();
        }
    }

    private function extractBlockMarkers(string $snippet): array
    {
        $lines = preg_split('/\R/', $snippet) ?: [];
        if (count($lines) < 2) {
            return [null, null];
        }

        $start = trim((string) $lines[0]);
        $end = trim((string) $lines[count($lines) - 1]);
        if (!str_starts_with($start, '// <sql-crud-generator:') || !str_ends_with($start, ':start>')) {
            return [null, null];
        }

        if (!str_starts_with($end, '// <sql-crud-generator:') || !str_ends_with($end, ':end>')) {
            return [null, null];
        }

        return [$start, $end];
    }

    private function replaceOrAppendBlock(string $existing, string $block, string $startMarker, string $endMarker): string
    {
        $escapedStart = preg_quote($startMarker, '/');
        $escapedEnd = preg_quote($endMarker, '/');
        $pattern = '/'.$escapedStart.'[\s\S]*?'.$escapedEnd.'/m';

        if (preg_match($pattern, $existing) === 1) {
            return (string) preg_replace($pattern, $block, $existing, 1);
        }

        return rtrim($existing)."\n\n".$block."\n";
    }

    private function removeLegacyModuleRouteLines(string $content, string $moduleName, string $mode): string
    {
        $escapedModule = preg_quote($moduleName, '/');
        $escapedMode = preg_quote($mode, '/');
        $pattern = '/^\s*\/\/\s*SQL CRUD Generator:\s*'.$escapedModule.'\s+'.$escapedMode.'\s+.*\R(?:^\s*Route::.*\R)+(?:^\s*\R)?/m';

        $updated = $content;
        while (preg_match($pattern, $updated) === 1) {
            $updated = (string) preg_replace($pattern, '', $updated, 1);
        }

        return $updated;
    }
}
