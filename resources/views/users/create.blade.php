@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Create User</h1>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        @include('users._form')
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection