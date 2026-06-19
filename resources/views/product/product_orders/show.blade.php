@extends('product.layouts.app')

@section('content')
<h1>ProductOrder Details</h1>
<table class="table">
    <tr><th>User Id</th><td>{{ $productOrder->user?->name ?? $productOrder->user_id }}</td></tr>
    <tr><th>Product Id</th><td>{{ $productOrder->product?->name ?? $productOrder->product_id }}</td></tr>
    <tr><th>Quantity</th><td>{{ $productOrder->quantity }}</td></tr>
    <tr><th>Order Date</th><td>{{ $productOrder->order_date }}</td></tr>
    <tr><th>Status</th><td>{{ $productOrder->status }}</td></tr>
</table>
<a href="{{ route('product.product_orders.index') }}" class="btn btn-primary">Back</a>
@endsection