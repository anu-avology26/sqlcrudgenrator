<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// <sql-crud-generator:Product:web:start>
Route::get('product/product_users', [\App\Http\Controllers\Product\ProductUserController::class, 'index'])->name('product.product_users.index');
Route::get('product/product_users/create', [\App\Http\Controllers\Product\ProductUserController::class, 'create'])->name('product.product_users.create');
Route::post('product/product_users', [\App\Http\Controllers\Product\ProductUserController::class, 'store'])->name('product.product_users.store');
Route::get('product/product_users/export', [\App\Http\Controllers\Product\ProductUserController::class, 'export'])->name('product.product_users.export');
Route::post('product/product_users/import', [\App\Http\Controllers\Product\ProductUserController::class, 'import'])->name('product.product_users.import');
Route::get('product/product_users/{id}/edit', [\App\Http\Controllers\Product\ProductUserController::class, 'edit'])->name('product.product_users.edit');
Route::put('product/product_users/{id}', [\App\Http\Controllers\Product\ProductUserController::class, 'update'])->name('product.product_users.update');
Route::delete('product/product_users/{id}', [\App\Http\Controllers\Product\ProductUserController::class, 'destroy'])->name('product.product_users.destroy');
Route::get('product/product_users/{id}', [\App\Http\Controllers\Product\ProductUserController::class, 'show'])->name('product.product_users.show');

Route::get('product/product_categories', [\App\Http\Controllers\Product\ProductCategoryController::class, 'index'])->name('product.product_categories.index');
Route::get('product/product_categories/create', [\App\Http\Controllers\Product\ProductCategoryController::class, 'create'])->name('product.product_categories.create');
Route::post('product/product_categories', [\App\Http\Controllers\Product\ProductCategoryController::class, 'store'])->name('product.product_categories.store');
Route::get('product/product_categories/export', [\App\Http\Controllers\Product\ProductCategoryController::class, 'export'])->name('product.product_categories.export');
Route::post('product/product_categories/import', [\App\Http\Controllers\Product\ProductCategoryController::class, 'import'])->name('product.product_categories.import');
Route::get('product/product_categories/{id}/edit', [\App\Http\Controllers\Product\ProductCategoryController::class, 'edit'])->name('product.product_categories.edit');
Route::put('product/product_categories/{id}', [\App\Http\Controllers\Product\ProductCategoryController::class, 'update'])->name('product.product_categories.update');
Route::delete('product/product_categories/{id}', [\App\Http\Controllers\Product\ProductCategoryController::class, 'destroy'])->name('product.product_categories.destroy');
Route::get('product/product_categories/{id}', [\App\Http\Controllers\Product\ProductCategoryController::class, 'show'])->name('product.product_categories.show');

Route::get('product/product_products', [\App\Http\Controllers\Product\ProductProductController::class, 'index'])->name('product.product_products.index');
Route::get('product/product_products/create', [\App\Http\Controllers\Product\ProductProductController::class, 'create'])->name('product.product_products.create');
Route::post('product/product_products', [\App\Http\Controllers\Product\ProductProductController::class, 'store'])->name('product.product_products.store');
Route::get('product/product_products/export', [\App\Http\Controllers\Product\ProductProductController::class, 'export'])->name('product.product_products.export');
Route::post('product/product_products/import', [\App\Http\Controllers\Product\ProductProductController::class, 'import'])->name('product.product_products.import');
Route::get('product/product_products/{id}/edit', [\App\Http\Controllers\Product\ProductProductController::class, 'edit'])->name('product.product_products.edit');
Route::put('product/product_products/{id}', [\App\Http\Controllers\Product\ProductProductController::class, 'update'])->name('product.product_products.update');
Route::delete('product/product_products/{id}', [\App\Http\Controllers\Product\ProductProductController::class, 'destroy'])->name('product.product_products.destroy');
Route::get('product/product_products/{id}', [\App\Http\Controllers\Product\ProductProductController::class, 'show'])->name('product.product_products.show');

Route::get('product/product_orders', [\App\Http\Controllers\Product\ProductOrderController::class, 'index'])->name('product.product_orders.index');
Route::get('product/product_orders/create', [\App\Http\Controllers\Product\ProductOrderController::class, 'create'])->name('product.product_orders.create');
Route::post('product/product_orders', [\App\Http\Controllers\Product\ProductOrderController::class, 'store'])->name('product.product_orders.store');
Route::get('product/product_orders/export', [\App\Http\Controllers\Product\ProductOrderController::class, 'export'])->name('product.product_orders.export');
Route::post('product/product_orders/import', [\App\Http\Controllers\Product\ProductOrderController::class, 'import'])->name('product.product_orders.import');
Route::get('product/product_orders/{id}/edit', [\App\Http\Controllers\Product\ProductOrderController::class, 'edit'])->name('product.product_orders.edit');
Route::put('product/product_orders/{id}', [\App\Http\Controllers\Product\ProductOrderController::class, 'update'])->name('product.product_orders.update');
Route::delete('product/product_orders/{id}', [\App\Http\Controllers\Product\ProductOrderController::class, 'destroy'])->name('product.product_orders.destroy');
Route::get('product/product_orders/{id}', [\App\Http\Controllers\Product\ProductOrderController::class, 'show'])->name('product.product_orders.show');

// <sql-crud-generator:Product:web:end>

// <sql-crud-generator:Subscription:web:start>
Route::get('subscription/subscription_plans', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'index'])->name('subscription.subscription_plans.index');
Route::get('subscription/subscription_plans/create', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'create'])->name('subscription.subscription_plans.create');
Route::post('subscription/subscription_plans', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'store'])->name('subscription.subscription_plans.store');
Route::get('subscription/subscription_plans/{id}/edit', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'edit'])->name('subscription.subscription_plans.edit');
Route::put('subscription/subscription_plans/{id}', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'update'])->name('subscription.subscription_plans.update');
Route::delete('subscription/subscription_plans/{id}', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'destroy'])->name('subscription.subscription_plans.destroy');
Route::get('subscription/subscription_plans/{id}', [\App\Http\Controllers\Subscription\SubscriptionPlanController::class, 'show'])->name('subscription.subscription_plans.show');

Route::get('subscription/subscription_subscribers', [\App\Http\Controllers\Subscription\SubscriptionSubscriberController::class, 'index'])->name('subscription.subscription_subscribers.index');
Route::get('subscription/subscription_subscribers/create', [\App\Http\Controllers\Subscription\SubscriptionSubscriberController::class, 'create'])->name('subscription.subscription_subscribers.create');
Route::post('subscription/subscription_subscribers', [\App\Http\Controllers\Subscription\SubscriptionSubscriberController::class, 'store'])->name('subscription.subscription_subscribers.store');
Route::get('subscription/subscription_subscribers/{id}/edit', [\App\Http\Controllers\Subscription\SubscriptionSubscriberController::class, 'edit'])->name('subscription.subscription_subscribers.edit');
Route::put('subscription/subscription_subscribers/{id}', [\App\Http\Controllers\Subscription\SubscriptionSubscriberController::class, 'update'])->name('subscription.subscription_subscribers.update');
Route::delete('subscription/subscription_subscribers/{id}', [\App\Http\Controllers\Subscription\SubscriptionSubscriberController::class, 'destroy'])->name('subscription.subscription_subscribers.destroy');
Route::get('subscription/subscription_subscribers/{id}', [\App\Http\Controllers\Subscription\SubscriptionSubscriberController::class, 'show'])->name('subscription.subscription_subscribers.show');

Route::get('subscription/subscription_subscriptions', [\App\Http\Controllers\Subscription\SubscriptionSubscriptionController::class, 'index'])->name('subscription.subscription_subscriptions.index');
Route::get('subscription/subscription_subscriptions/create', [\App\Http\Controllers\Subscription\SubscriptionSubscriptionController::class, 'create'])->name('subscription.subscription_subscriptions.create');
Route::post('subscription/subscription_subscriptions', [\App\Http\Controllers\Subscription\SubscriptionSubscriptionController::class, 'store'])->name('subscription.subscription_subscriptions.store');
Route::get('subscription/subscription_subscriptions/{id}/edit', [\App\Http\Controllers\Subscription\SubscriptionSubscriptionController::class, 'edit'])->name('subscription.subscription_subscriptions.edit');
Route::put('subscription/subscription_subscriptions/{id}', [\App\Http\Controllers\Subscription\SubscriptionSubscriptionController::class, 'update'])->name('subscription.subscription_subscriptions.update');
Route::delete('subscription/subscription_subscriptions/{id}', [\App\Http\Controllers\Subscription\SubscriptionSubscriptionController::class, 'destroy'])->name('subscription.subscription_subscriptions.destroy');
Route::get('subscription/subscription_subscriptions/{id}', [\App\Http\Controllers\Subscription\SubscriptionSubscriptionController::class, 'show'])->name('subscription.subscription_subscriptions.show');

// <sql-crud-generator:Subscription:web:end>
