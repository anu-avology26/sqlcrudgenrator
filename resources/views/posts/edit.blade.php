@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Post</h1>

    <form action="{{ route('posts.update', $post) }}" method="POST">
        @csrf
        @method('PUT')
        @include('posts._form', ['post' => $post])
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection