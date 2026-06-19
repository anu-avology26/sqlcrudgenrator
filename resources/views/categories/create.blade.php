@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create Category</h1>

    <form action="{{ route('categories.store') }}" method="POST">
        @csrf
        @include('categories._form')
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection