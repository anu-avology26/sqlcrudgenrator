@extends('subscription.layouts.app')

@section('content')
<h1>Create SubscriptionSubscriber</h1>
<form action="{{ route('subscription.subscription_subscribers.store') }}" method="POST">
    @csrf
    @include('subscription.subscription_subscribers._form')
    <button type="submit" class="btn btn-primary">Save</button>
</form>

@endsection