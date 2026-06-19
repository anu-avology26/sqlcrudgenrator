@extends('subscription.layouts.app')

@section('content')
<h1>SubscriptionPlan List</h1>
<div class="toolbar">
    <a href="{{ route('subscription.subscription_plans.create') }}" class="btn btn-primary">Create SubscriptionPlan</a>

</div>
<form method="GET" action="{{ route('subscription.subscription_plans.index') }}" class="toolbar" style="margin-top: 8px;">
    <input type="text" name="search" class="form-control" style="max-width: 320px;" placeholder="Search..." value="{{ request('search', '') }}">
    <select name="sort" class="form-control" style="max-width: 220px;">
            <option value="created_at" {{ request('sort', 'created_at') === 'created_at' ? 'selected' : '' }}>Created At</option>
            <option value="updated_at" {{ request('sort', 'created_at') === 'updated_at' ? 'selected' : '' }}>Updated At</option>
            <option value="name" {{ request('sort', 'created_at') === 'name' ? 'selected' : '' }}>Name</option>
            <option value="code" {{ request('sort', 'created_at') === 'code' ? 'selected' : '' }}>Code</option>
            <option value="price" {{ request('sort', 'created_at') === 'price' ? 'selected' : '' }}>Price</option>
            <option value="billing_cycle" {{ request('sort', 'created_at') === 'billing_cycle' ? 'selected' : '' }}>Billing Cycle</option>
            <option value="is_active" {{ request('sort', 'created_at') === 'is_active' ? 'selected' : '' }}>Is Active</option>
    </select>
    <select name="direction" class="form-control" style="max-width: 140px;">
        <option value="desc" {{ request('direction', 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
        <option value="asc" {{ request('direction', 'desc') === 'asc' ? 'selected' : '' }}>Asc</option>
    </select>
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a href="{{ route('subscription.subscription_plans.index') }}" class="btn btn-secondary">Reset</a>
</form>
<table class="table" style="margin-top: 16px;">
    <thead>
        <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Code</th>
                <th>Price</th>
                <th>Billing Cycle</th>
                <th>Is Active</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($subscriptionPlans as $item)
            <tr>
                    <td>{{ $item->getKey() }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->code }}</td>
                    <td>{{ $item->price }}</td>
                    <td>{{ $item->billing_cycle }}</td>
                    <td>{{ $item->is_active }}</td>
                <td>
                    <a href="{{ route('subscription.subscription_plans.show', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-primary">View</a>
                    <a href="{{ route('subscription.subscription_plans.edit', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('subscription.subscription_plans.destroy', ['id' => $item->getKey()]) }}" method="POST" style="display:inline;">
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
{{ $subscriptionPlans->links() }}

@endsection