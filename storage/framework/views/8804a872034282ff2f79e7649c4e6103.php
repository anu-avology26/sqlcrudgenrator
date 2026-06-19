<?php $__env->startSection('content'); ?>
<h1>ProductOrder Details</h1>
<table class="table">
    <tr><th>User Id</th><td><?php echo e($productOrder->user?->name ?? $productOrder->user_id); ?></td></tr>
    <tr><th>Product Id</th><td><?php echo e($productOrder->product?->name ?? $productOrder->product_id); ?></td></tr>
    <tr><th>Quantity</th><td><?php echo e($productOrder->quantity); ?></td></tr>
    <tr><th>Order Date</th><td><?php echo e($productOrder->order_date); ?></td></tr>
    <tr><th>Status</th><td><?php echo e($productOrder->status); ?></td></tr>
</table>
<a href="<?php echo e(route('product.product_orders.index')); ?>" class="btn btn-primary">Back</a>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('product.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\sql2laravel\resources\views/product/product_orders/show.blade.php ENDPATH**/ ?>