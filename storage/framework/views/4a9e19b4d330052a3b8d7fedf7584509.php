<?php $__env->startSection('content'); ?>
<h1>ProductProduct List</h1>
<div class="toolbar">
    <a href="<?php echo e(route('product.product_products.create')); ?>" class="btn btn-primary">Create ProductProduct</a>
    <a href="<?php echo e(route('product.product_products.export')); ?>" class="btn btn-secondary">Export CSV</a>
    <form action="<?php echo e(route('product.product_products.import')); ?>" method="POST" enctype="multipart/form-data" style="display:inline-block;">
        <?php echo csrf_field(); ?>
        <input type="file" name="csv_file" required>
        <button type="submit" class="btn btn-secondary">Import CSV</button>
    </form>
</div>
<form method="GET" action="<?php echo e(route('product.product_products.index')); ?>" class="toolbar" style="margin-top: 8px;">
    <input type="text" name="search" class="form-control" style="max-width: 320px;" placeholder="Search..." value="<?php echo e(request('search', '')); ?>">
    <select name="sort" class="form-control" style="max-width: 220px;">
            <option value="created_at" <?php echo e(request('sort', 'created_at') === 'created_at' ? 'selected' : ''); ?>>Created At</option>
            <option value="updated_at" <?php echo e(request('sort', 'created_at') === 'updated_at' ? 'selected' : ''); ?>>Updated At</option>
            <option value="category_id" <?php echo e(request('sort', 'created_at') === 'category_id' ? 'selected' : ''); ?>>Category Id</option>
            <option value="name" <?php echo e(request('sort', 'created_at') === 'name' ? 'selected' : ''); ?>>Name</option>
            <option value="sku" <?php echo e(request('sort', 'created_at') === 'sku' ? 'selected' : ''); ?>>Sku</option>
            <option value="price" <?php echo e(request('sort', 'created_at') === 'price' ? 'selected' : ''); ?>>Price</option>
            <option value="is_active" <?php echo e(request('sort', 'created_at') === 'is_active' ? 'selected' : ''); ?>>Is Active</option>
    </select>
    <select name="direction" class="form-control" style="max-width: 140px;">
        <option value="desc" <?php echo e(request('direction', 'desc') === 'desc' ? 'selected' : ''); ?>>Desc</option>
        <option value="asc" <?php echo e(request('direction', 'desc') === 'asc' ? 'selected' : ''); ?>>Asc</option>
    </select>
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a href="<?php echo e(route('product.product_products.index')); ?>" class="btn btn-secondary">Reset</a>
</form>
<table class="table" style="margin-top: 16px;">
    <thead>
        <tr>
                <th>ID</th>
                <th>Category Id</th>
                <th>Name</th>
                <th>Sku</th>
                <th>Price</th>
                <th>Is Active</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $productProducts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
                    <td><?php echo e($item->getKey()); ?></td>
                    <td><?php echo e($item->category?->name ?? $item->category_id); ?></td>
                    <td><?php echo e($item->name); ?></td>
                    <td><?php echo e($item->sku); ?></td>
                    <td><?php echo e($item->price); ?></td>
                    <td><?php echo e($item->is_active); ?></td>
                <td>
                    <a href="<?php echo e(route('product.product_products.show', ['id' => $item->getKey()])); ?>" class="btn btn-sm btn-primary">View</a>
                    <a href="<?php echo e(route('product.product_products.edit', ['id' => $item->getKey()])); ?>" class="btn btn-sm btn-warning">Edit</a>
                    <form action="<?php echo e(route('product.product_products.destroy', ['id' => $item->getKey()])); ?>" method="POST" style="display:inline;" data-ajax="delete">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="99">No data found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
<?php echo e($productProducts->links()); ?>

<?php $__env->startPush('scripts'); ?>
<script>
document.querySelectorAll('form[data-ajax="delete"]').forEach(function (form) {
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!confirm('Are you sure?')) return;

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
            location.reload();
        } else {
            alert('Delete request failed.');
        }
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('product.layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\sql2laravel\resources\views/product/product_products/index.blade.php ENDPATH**/ ?>