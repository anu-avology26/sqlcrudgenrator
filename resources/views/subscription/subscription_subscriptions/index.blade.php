@extends('subscription.layouts.app')

@section('content')
<h1>SubscriptionSubscription List</h1>
<div class="toolbar">
    <a href="{{ route('subscription.subscription_subscriptions.create') }}" class="btn btn-primary">Create SubscriptionSubscription</a>

</div>
<form method="GET" action="{{ route('subscription.subscription_subscriptions.index') }}" class="toolbar" style="margin-top: 8px;">
    <input type="text" name="search" class="form-control" style="max-width: 320px;" placeholder="Search..." value="{{ request('search', '') }}">
    <select name="sort" class="form-control" style="max-width: 220px;">
            <option value="created_at" {{ request('sort', 'created_at') === 'created_at' ? 'selected' : '' }}>Created At</option>
            <option value="updated_at" {{ request('sort', 'created_at') === 'updated_at' ? 'selected' : '' }}>Updated At</option>
            <option value="subscriber_id" {{ request('sort', 'created_at') === 'subscriber_id' ? 'selected' : '' }}>Subscriber Id</option>
            <option value="plan_id" {{ request('sort', 'created_at') === 'plan_id' ? 'selected' : '' }}>Plan Id</option>
            <option value="starts_at" {{ request('sort', 'created_at') === 'starts_at' ? 'selected' : '' }}>Starts At</option>
            <option value="ends_at" {{ request('sort', 'created_at') === 'ends_at' ? 'selected' : '' }}>Ends At</option>
            <option value="status" {{ request('sort', 'created_at') === 'status' ? 'selected' : '' }}>Status</option>
            <option value="name" {{ request('sort', 'created_at') === 'name' ? 'selected' : '' }}>Name</option>
    </select>
    <select name="direction" class="form-control" style="max-width: 140px;">
        <option value="desc" {{ request('direction', 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
        <option value="asc" {{ request('direction', 'desc') === 'asc' ? 'selected' : '' }}>Asc</option>
    </select>
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a href="{{ route('subscription.subscription_subscriptions.index') }}" class="btn btn-secondary">Reset</a>
</form>
<table class="table" style="margin-top: 16px;">
    <thead>
        <tr>
                <th>ID</th>
                <th>Subscriber Id</th>
                <th>Plan Id</th>
                <th>Starts At</th>
                <th>Ends At</th>
                <th>Status</th>
                <th>Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($subscriptionSubscriptions as $item)
            <tr>
                    <td>{{ $item->getKey() }}</td>
                    <td>{{ $item->subscriber?->name ?? $item->subscriber_id }}</td>
                    <td>{{ $item->plan?->name ?? $item->plan_id }}</td>
                    <td>{{ $item->starts_at }}</td>
                    <td>{{ $item->ends_at }}</td>
                    <td>{{ $item->status }}</td>
                    <td>{{ $item->name }}</td>
                <td>
                    <a href="{{ route('subscription.subscription_subscriptions.show', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-primary">View</a>
                    <a href="{{ route('subscription.subscription_subscriptions.edit', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('subscription.subscription_subscriptions.destroy', ['id' => $item->getKey()]) }}" method="POST" style="display:inline;">
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
{{ $subscriptionSubscriptions->links() }}

@endsection