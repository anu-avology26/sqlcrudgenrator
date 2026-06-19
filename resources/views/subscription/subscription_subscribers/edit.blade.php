@extends('subscription.layouts.app')

@section('content')
<h1>Edit SubscriptionSubscriber</h1>
<form action="{{ route('subscription.subscription_subscribers.update', ['id' => $subscriptionSubscriber->getKey()]) }}" method="POST">
    @csrf
    @method('PUT')
    @include('subscription.subscription_subscribers._form', ['subscriptionSubscriber' => $subscriptionSubscriber])
    <button type="submit" class="btn btn-primary">Update</button>
</form>

@endsection