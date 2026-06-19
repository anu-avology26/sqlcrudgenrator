@extends('subscription.layouts.app')

@section('content')
<h1>Edit SubscriptionSubscription</h1>
<form action="{{ route('subscription.subscription_subscriptions.update', ['id' => $subscriptionSubscription->getKey()]) }}" method="POST">
    @csrf
    @method('PUT')
    @include('subscription.subscription_subscriptions._form', ['subscriptionSubscription' => $subscriptionSubscription])
    <button type="submit" class="btn btn-primary">Update</button>
</form>

@endsection