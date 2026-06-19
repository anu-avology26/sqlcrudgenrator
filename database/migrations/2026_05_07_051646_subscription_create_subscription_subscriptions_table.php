<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('subscription_subscriptions');
        Schema::enableForeignKeyConstraints();

        Schema::create('subscription_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('subscriber_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->string('status', 255)->nullable();
            $table->string('name', 255);
            $table->timestamps();
            $table->foreign('subscriber_id')->references('id')->on('subscription_subscribers');
            $table->foreign('plan_id')->references('id')->on('subscription_plans');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_subscriptions');
    }
};