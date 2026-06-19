<?php

namespace SqlCrudGenerator\Services\Generators;

class ModuleProviderGenerator
{
    public function generate(array $context): string
    {
        $moduleNamespace = $context['module_namespace'];
        $viewNamespace = $context['view_namespace'];
        $providerName = $context['module_name'].'ServiceProvider';

        return <<<PHP
<?php

namespace {$moduleNamespace};

use Illuminate\Support\ServiceProvider;

class {$providerName} extends ServiceProvider
{
    public function boot(): void
    {
        if (is_file(__DIR__.'/routes/web.php')) {
            \$this->loadRoutesFrom(__DIR__.'/routes/web.php');
        }

        if (is_file(__DIR__.'/routes/api.php')) {
            \$this->loadRoutesFrom(__DIR__.'/routes/api.php');
        }

        \$this->loadViewsFrom(__DIR__.'/resources/views', '{$viewNamespace}');
    }
}
PHP;
    }
}
