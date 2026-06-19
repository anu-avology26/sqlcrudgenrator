@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Post List</h1>

    <a href="{{ route('posts.create') }}" class="btn btn-primary">Create Post</a>

    <table class="table" style="margin-top: 16px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>User Id</th>
                <th>Category Id</th>
                <th>Title</th>
                <th>Body</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($posts as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->user_id }}</td>
                    <td>{{ $item->category_id }}</td>
                    <td>{{ $item->title }}</td>
                    <td>{{ $item->body }}</td>
                    <td>
                        <a href="{{ route('posts.edit', $item) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('posts.destroy', $item) }}" method="POST" style="display: inline;">
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

    {{ $posts->links() }}
</div>
@endsection