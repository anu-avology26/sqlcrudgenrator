<?php

namespace SqlCrudGenerator;

use Illuminate\Support\ServiceProvider;

class SqlCrudGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sql-crud-generator.php', 'sql-crud-generator');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sql-crud-generator');

        $this->publishes([
            __DIR__.'/../config/sql-crud-generator.php' => config_path('sql-crud-generator.php'),
        ], 'sql-crud-generator-config');
    }
}
