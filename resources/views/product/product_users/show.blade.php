@extends('product.layouts.app')

@section('content')
<h1>ProductUser Details</h1>
<table class="table">
    <tr><th>Name</th><td>{{ $productUser->name }}</td></tr>
    <tr><th>Email</th><td>{{ $productUser->email }}</td></tr>
    <tr><th>Phone</th><td>{{ $productUser->phone }}</td></tr>
    <tr><th>Is Active</th><td>{{ $productUser->is_active }}</td></tr>
</table>
<a href="{{ route('product.product_users.index') }}" class="btn btn-primary">Back</a>
@endsection