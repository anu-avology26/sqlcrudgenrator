@extends('subscription.layouts.app')

@section('content')
<h1>SubscriptionSubscription Details</h1>
<table class="table">
    <tr><th>Subscriber Id</th><td>{{ $subscriptionSubscription->subscriber?->name ?? $subscriptionSubscription->subscriber_id }}</td></tr>
    <tr><th>Plan Id</th><td>{{ $subscriptionSubscription->plan?->name ?? $subscriptionSubscription->plan_id }}</td></tr>
    <tr><th>Starts At</th><td>{{ $subscriptionSubscription->starts_at }}</td></tr>
    <tr><th>Ends At</th><td>{{ $subscriptionSubscription->ends_at }}</td></tr>
    <tr><th>Status</th><td>{{ $subscriptionSubscription->status }}</td></tr>
    <tr><th>Name</th><td>{{ $subscriptionSubscription->name }}</td></tr>
</table>
<a href="{{ route('subscription.subscription_subscriptions.index') }}" class="btn btn-primary">Back</a>
@endsection