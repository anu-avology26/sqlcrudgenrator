<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('subscription_plans');
        Schema::enableForeignKeyConstraints();

        Schema::create('subscription_plans', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 255)->nullable();
            $table->string('code', 255)->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('billing_cycle', 255)->nullable();
            $table->boolean('is_active')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};