<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('product_categories');
        Schema::enableForeignKeyConstraints();

        Schema::create('product_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('status', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};