<?php $__env->startSection('content'); ?>
<h1>Edit ProductProduct</h1>
<form action="<?php echo e(route('product.product_products.update', ['id' => $productProduct->getKey()])); ?>" method="POST" data-ajax="submit">
    <?php echo csrf_field(); ?>
    <?php echo method_field('PUT'); ?>
    <?php echo $__env->make('product.product_products._form', ['productProduct' => $productProduct], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <button type="submit" class="btn btn-primary">Update</button>
</form>
<?php $__env->startPush('scripts'); ?>
<script>
document.querySelectorAll('form[data-ajax="submit"]').forEach(function (form) {
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: new FormData(form),
        });

        if (response.ok) {
            alert('edit success');
            window.location.href = document.referrer || '/';
        } else {
            const data = await response.json().catch(function () { return {}; });
            alert(data.message || 'Request failed.');
        }
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('product.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\sql2laravel\resources\views/product/product_products/edit.blade.php ENDPATH**/ ?>