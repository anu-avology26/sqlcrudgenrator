@extends('subscription.layouts.app')

@section('content')
<h1>Create SubscriptionPlan</h1>
<form action="{{ route('subscription.subscription_plans.store') }}" method="POST">
    @csrf
    @include('subscription.subscription_plans._form')
    <button type="submit" class="btn btn-primary">Save</button>
</form>

@endsection