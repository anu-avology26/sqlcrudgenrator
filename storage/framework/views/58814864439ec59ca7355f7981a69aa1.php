<?php $__env->startSection('content'); ?>
<h1>ProductUser Details</h1>
<table class="table">
    <tr><th>Name</th><td><?php echo e($productUser->name); ?></td></tr>
    <tr><th>Email</th><td><?php echo e($productUser->email); ?></td></tr>
    <tr><th>Phone</th><td><?php echo e($productUser->phone); ?></td></tr>
    <tr><th>Is Active</th><td><?php echo e($productUser->is_active); ?></td></tr>
</table>
<a href="<?php echo e(route('product.product_users.index')); ?>" class="btn btn-primary">Back</a>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('product.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\sql2laravel\resources\views/product/product_users/show.blade.php ENDPATH**/ ?>