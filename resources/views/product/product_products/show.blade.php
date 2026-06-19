@extends('product.layouts.app')

@section('content')
<h1>ProductProduct Details</h1>
<table class="table">
    <tr><th>Category Id</th><td>{{ $productProduct->category?->name ?? $productProduct->category_id }}</td></tr>
    <tr><th>Name</th><td>{{ $productProduct->name }}</td></tr>
    <tr><th>Sku</th><td>{{ $productProduct->sku }}</td></tr>
    <tr><th>Price</th><td>{{ $productProduct->price }}</td></tr>
    <tr><th>Is Active</th><td>{{ $productProduct->is_active }}</td></tr>
</table>
<a href="{{ route('product.product_products.index') }}" class="btn btn-primary">Back</a>
@endsection