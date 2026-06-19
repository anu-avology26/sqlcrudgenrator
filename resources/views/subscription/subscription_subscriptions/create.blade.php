@extends('subscription.layouts.app')

@section('content')
<h1>Create SubscriptionSubscription</h1>
<form action="{{ route('subscription.subscription_subscriptions.store') }}" method="POST">
    @csrf
    @include('subscription.subscription_subscriptions._form')
    <button type="submit" class="btn btn-primary">Save</button>
</form>

@endsection