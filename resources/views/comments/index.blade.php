@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Comment List</h1>

    <a href="{{ route('comments.create') }}" class="btn btn-primary">Create Comment</a>

    <table class="table" style="margin-top: 16px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Post Id</th>
                <th>User Id</th>
                <th>Comment</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($comments as $item)
                <tr>
                    <td>{{ $item->id }}</td>
                    <td>{{ $item->post_id }}</td>
                    <td>{{ $item->user_id }}</td>
                    <td>{{ $item->comment }}</td>
                    <td>
                        <a href="{{ route('comments.edit', $item) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('comments.destroy', $item) }}" method="POST" style="display: inline;">
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

    {{ $comments->links() }}
</div>
@endsection