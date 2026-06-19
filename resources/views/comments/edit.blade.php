@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Comment</h1>

    <form action="{{ route('comments.update', $comment) }}" method="POST">
        @csrf
        @method('PUT')
        @include('comments._form', ['comment' => $comment])
        <button type="submit" class="btn btn-primary">Update</button>
    </form>
</div>
@endsection