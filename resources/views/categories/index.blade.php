@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Category List</h1>

    <a href="{{ route('categories.create') }}" class="btn btn-primary">Create Category</a>

    <table class="table" style="margin-top: 16px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->name }}</td>
                    <td>
                        <a href="{{ route('categories.edit', $item) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('categories.destroy', $item) }}" method="POST" style="display: inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="99">No data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $categories->links() }}
</div>
@endsection