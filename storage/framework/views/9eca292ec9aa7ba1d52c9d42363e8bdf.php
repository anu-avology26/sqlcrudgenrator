<?php $__env->startSection('content'); ?>
<h1>ProductProduct Details</h1>
<table class="table">
    <tr><th>Category Id</th><td><?php echo e($productProduct->category?->name ?? $productProduct->category_id); ?></td></tr>
    <tr><th>Name</th><td><?php echo e($productProduct->name); ?></td></tr>
    <tr><th>Sku</th><td><?php echo e($productProduct->sku); ?></td></tr>
    <tr><th>Price</th><td><?php echo e($productProduct->price); ?></td></tr>
    <tr><th>Is Active</th><td><?php echo e($productProduct->is_active); ?></td></tr>
</table>
<a href="<?php echo e(route('product.product_products.index')); ?>" class="btn btn-primary">Back</a>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('product.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\sql2laravel\resources\views/product/product_products/show.blade.php ENDPATH**/ ?>