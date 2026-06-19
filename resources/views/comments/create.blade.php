@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Comment</h1>

    <form action="{{ route('comments.store') }}" method="POST">
        @csrf
        @include('comments._form')
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection