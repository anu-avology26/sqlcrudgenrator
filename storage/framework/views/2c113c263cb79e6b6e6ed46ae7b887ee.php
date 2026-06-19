<?php $__env->startSection('content'); ?>
<h1>ProductCategory Details</h1>
<table class="table">
    <tr><th>Name</th><td><?php echo e($productCategory->name); ?></td></tr>
    <tr><th>Status</th><td><?php echo e($productCategory->status); ?></td></tr>
</table>
<a href="<?php echo e(route('product.product_categories.index')); ?>" class="btn btn-primary">Back</a>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('product.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\sql2laravel\resources\views/product/product_categories/show.blade.php ENDPATH**/ ?>