<?php

use Illuminate\Support\Facades\Route;
use SqlCrudGenerator\Http\Controllers\GeneratorController;

Route::middleware(config('sql-crud-generator.middleware', ['web']))
    ->prefix(config('sql-crud-generator.route_prefix', 'sql-crud-generator'))
    ->name('sql-crud-generator.')
    ->group(function (): void {
        Route::get('/', [GeneratorController::class, 'index'])->name('index');
        Route::post('/generate', [GeneratorController::class, 'generate'])->name('generate');
    });
