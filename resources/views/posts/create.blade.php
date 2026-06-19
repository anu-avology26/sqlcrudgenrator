@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Post</h1>

    <form action="{{ route('posts.store') }}" method="POST">
        @csrf
        @include('posts._form')
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection