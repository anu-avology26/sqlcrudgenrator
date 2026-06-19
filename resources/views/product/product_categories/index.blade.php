@extends('product.layouts.app')

@section('content')
<h1>ProductCategory List</h1>
<div class="toolbar">
    <a href="{{ route('product.product_categories.create') }}" class="btn btn-primary">Create ProductCategory</a>
    <a href="{{ route('product.product_categories.export') }}" class="btn btn-secondary">Export CSV</a>
    <form action="{{ route('product.product_categories.import') }}" method="POST" enctype="multipart/form-data" style="display:inline-block;">
        @csrf
        <input type="file" name="csv_file" required>
        <button type="submit" class="btn btn-secondary">Import CSV</button>
    </form>
</div>
<form method="GET" action="{{ route('product.product_categories.index') }}" class="toolbar" style="margin-top: 8px;">
    <input type="text" name="search" class="form-control" style="max-width: 320px;" placeholder="Search..." value="{{ request('search', '') }}">
    <select name="sort" class="form-control" style="max-width: 220px;">
            <option value="created_at" {{ request('sort', 'created_at') === 'created_at' ? 'selected' : '' }}>Created At</option>
            <option value="updated_at" {{ request('sort', 'created_at') === 'updated_at' ? 'selected' : '' }}>Updated At</option>
            <option value="name" {{ request('sort', 'created_at') === 'name' ? 'selected' : '' }}>Name</option>
            <option value="status" {{ request('sort', 'created_at') === 'status' ? 'selected' : '' }}>Status</option>
    </select>
    <select name="direction" class="form-control" style="max-width: 140px;">
        <option value="desc" {{ request('direction', 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
        <option value="asc" {{ request('direction', 'desc') === 'asc' ? 'selected' : '' }}>Asc</option>
    </select>
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a href="{{ route('product.product_categories.index') }}" class="btn btn-secondary">Reset</a>
</form>
<table class="table" style="margin-top: 16px;">
    <thead>
        <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($productCategories as $item)
            <tr>
                    <td>{{ $item->getKey() }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->status }}</td>
                <td>
                    <a href="{{ route('product.product_categories.show', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-primary">View</a>
                    <a href="{{ route('product.product_categories.edit', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('product.product_categories.destroy', ['id' => $item->getKey()]) }}" method="POST" style="display:inline;" data-ajax="delete">
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
{{ $productCategories->links() }}
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