@extends('subscription.layouts.app')

@section('content')
<h1>Edit SubscriptionPlan</h1>
<form action="{{ route('subscription.subscription_plans.update', ['id' => $subscriptionPlan->getKey()]) }}" method="POST">
    @csrf
    @method('PUT')
    @include('subscription.subscription_plans._form', ['subscriptionPlan' => $subscriptionPlan])
    <button type="submit" class="btn btn-primary">Update</button>
</form>

@endsection