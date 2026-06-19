@extends('subscription.layouts.app')

@section('content')
<h1>SubscriptionSubscriber List</h1>
<div class="toolbar">
    <a href="{{ route('subscription.subscription_subscribers.create') }}" class="btn btn-primary">Create SubscriptionSubscriber</a>

</div>
<form method="GET" action="{{ route('subscription.subscription_subscribers.index') }}" class="toolbar" style="margin-top: 8px;">
    <input type="text" name="search" class="form-control" style="max-width: 320px;" placeholder="Search..." value="{{ request('search', '') }}">
    <select name="sort" class="form-control" style="max-width: 220px;">
            <option value="created_at" {{ request('sort', 'created_at') === 'created_at' ? 'selected' : '' }}>Created At</option>
            <option value="updated_at" {{ request('sort', 'created_at') === 'updated_at' ? 'selected' : '' }}>Updated At</option>
            <option value="name" {{ request('sort', 'created_at') === 'name' ? 'selected' : '' }}>Name</option>
            <option value="email" {{ request('sort', 'created_at') === 'email' ? 'selected' : '' }}>Email</option>
            <option value="phone" {{ request('sort', 'created_at') === 'phone' ? 'selected' : '' }}>Phone</option>
    </select>
    <select name="direction" class="form-control" style="max-width: 140px;">
        <option value="desc" {{ request('direction', 'desc') === 'desc' ? 'selected' : '' }}>Desc</option>
        <option value="asc" {{ request('direction', 'desc') === 'asc' ? 'selected' : '' }}>Asc</option>
    </select>
    <button type="submit" class="btn btn-secondary">Apply</button>
    <a href="{{ route('subscription.subscription_subscribers.index') }}" class="btn btn-secondary">Reset</a>
</form>
<table class="table" style="margin-top: 16px;">
    <thead>
        <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($subscriptionSubscribers as $item)
            <tr>
                    <td>{{ $item->getKey() }}</td>
                    <td>{{ $item->name }}</td>
                    <td>{{ $item->email }}</td>
                    <td>{{ $item->phone }}</td>
                <td>
                    <a href="{{ route('subscription.subscription_subscribers.show', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-primary">View</a>
                    <a href="{{ route('subscription.subscription_subscribers.edit', ['id' => $item->getKey()]) }}" class="btn btn-sm btn-warning">Edit</a>
                    <form action="{{ route('subscription.subscription_subscribers.destroy', ['id' => $item->getKey()]) }}" method="POST" style="display:inline;">
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
{{ $subscriptionSubscribers->links() }}

@endsection