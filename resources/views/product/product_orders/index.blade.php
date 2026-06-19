@extends('product.layouts.app')

@section('content')
<h1>ProductOrder List</h1>
<div class="toolbar">
    <a href="{{ route('product.product_orders.create') }}" class="btn btn-primary">Create ProductOrder</a>
    <a href="{{ route('product.product_orders.export') }}" class="btn btn-secondary">Export CSV</a>
    <form action="{{ route('product.product_orders.import') }}" method="POST" enctype="multipart/form-data" style="display:inline-block;">
        @csrf
        <input type="file" name="csv_file" required>
        <button type="submit" class="btn btn-secondary">Import CSV</button>
    </form>
</div>
<form method="GET" action="{{ route('product.product_orders.index') }}" class="toolbar" style="margin-top: 8px;">
    <input type="text" name="search" class="form-control" style="max-width: 320px;" placeholder="Search..." value="{{ request('search', '') }}">
    <select name="sort" class="form-control" style="max-width: 220px;">
            <option value="created_at" {{ request('sort', 'created_at') === 'created_at' ? 'selected' : '' }}>Created At</option>
            <option value="updated_at" {{ request('sort', 'created_at') === 'updated_at' ? 'selected' : '' }}>Updated At</option>
            <option value="user_id" {{ request('sort', 'created_at') === 'user_id' ? 'selected' : '' }}>User Id</option>
            <option value="product_id" {{ request('sort', 'created_at') === 'product_id' ? 'selected' : '' }}>Product Id</option>
            <option value="quantity" {{ request('sort', 'created_at') === 'quantity' ? 'selected' : '' }}>Quantity</option>
            <option value="order_date" {{ request('sort', 'created_at') === 'order_date' ? 'selected' : '' }}>Order Date</option>
            <option value="status" {{ request('sort', 'created_at') === 'status' ? 'selected' : '' }}>Status</option>
    </select>
    <select name="direction" class="form-control" style="max-width: 140px;">
        <option value="desc" {{ request('direction', 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
        <option value="asc" {{ request('direction', 'desc') === 'asc' ? 'selected' : '' }}>Asc</option>
    </select>
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a href="{{ route('product.product_orders.index') }}" class="btn btn-secondary">Reset</a>
</form>
<table class="table" style="margin-top: 16px;">
    <thead>
        <tr>
                <th>ID</th>
                <th>User Id</th>
                <th>Product Id</th>
                <th>Quantity</th>
                <th>Order Date</th>
                <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($productOrders as $item)
            <tr>
                    <td>{{ $item->getKey() }}</td>
                    <td>{{ $item->user?->name ?? $item->user_id }}</td>
                    <td>{{ $item->product?->name ?? $item->product_id }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->order_date }}</td>
                    <td>{{ $item->status }}</td>
                <td>
                    <a href="{{ route('product.product_orders.show', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-primary">View</a>
                    <a href="{{ route('product.product_orders.edit', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('product.product_orders.destroy', ['id' => $item->getKey()]) }}" method="POST" style="display:inline;" data-ajax="delete">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="99">No data found.</td></tr>
        @endforelse
    </tbody>
</table>
{{ $productOrders->links() }}
@push('scripts')
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
@endpush
@endsection