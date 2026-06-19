@extends('subscription.layouts.app')

@section('content')
<h1>SubscriptionSubscriber Details</h1>
<table class="table">
    <tr><th>Name</th><td>{{ $subscriptionSubscriber->name }}</td></tr>
    <tr><th>Email</th><td>{{ $subscriptionSubscriber->email }}</td></tr>
    <tr><th>Phone</th><td>{{ $subscriptionSubscriber->phone }}</td></tr>
</table>
<a href="{{ route('subscription.subscription_subscribers.index') }}" class="btn btn-primary">Back</a>
@endsection