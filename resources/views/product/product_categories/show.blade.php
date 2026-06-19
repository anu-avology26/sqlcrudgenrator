@extends('product.layouts.app')

@section('content')
<h1>ProductCategory Details</h1>
<table class="table">
    <tr><th>Name</th><td>{{ $productCategory->name }}</td></tr>
    <tr><th>Status</th><td>{{ $productCategory->status }}</td></tr>
</table>
<a href="{{ route('product.product_categories.index') }}" class="btn btn-primary">Back</a>
@endsection