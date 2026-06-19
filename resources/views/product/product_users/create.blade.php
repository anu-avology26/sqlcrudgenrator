@extends('product.layouts.app')

@section('content')
<h1>Create ProductUser</h1>
<form action="{{ route('product.product_users.store') }}" method="POST" data-ajax="submit">
    @csrf
    @include('product.product_users._form')
    <button type="submit" class="btn btn-primary">Save</button>
</form>
@push('scripts')
<script>
document.querySelectorAll('form[data-ajax="submit"]').forEach(function (form) {
    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: new FormData(form),
        });

        if (response.ok) {
            alert('create success');
            window.location.href = document.referrer || '/';
        } else {
            const data = await response.json().catch(function () { return {}; });
            alert(data.message || 'Request failed.');
        }
    });
});
</script>
@endpush
@endsection