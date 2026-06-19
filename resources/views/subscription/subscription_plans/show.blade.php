@extends('subscription.layouts.app')

@section('content')
<h1>SubscriptionPlan Details</h1>
<table class="table">
    <tr><th>Name</th><td>{{ $subscriptionPlan->name }}</td></tr>
    <tr><th>Code</th><td>{{ $subscriptionPlan->code }}</td></tr>
    <tr><th>Price</th><td>{{ $subscriptionPlan->price }}</td></tr>
    <tr><th>Billing Cycle</th><td>{{ $subscriptionPlan->billing_cycle }}</td></tr>
    <tr><th>Is Active</th><td>{{ $subscriptionPlan->is_active }}</td></tr>
</table>
<a href="{{ route('subscription.subscription_plans.index') }}" class="btn btn-primary">Back</a>
@endsection