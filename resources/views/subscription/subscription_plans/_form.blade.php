<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $subscriptionPlan->name ?? '') }}" class="form-control">
    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="code" class="form-label">Code</label>
    <input type="text" id="code" name="code" value="{{ old('code', $subscriptionPlan->code ?? '') }}" class="form-control">
    @error('code') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="price" class="form-label">Price</label>
    <input type="number" id="price" name="price" value="{{ old('price', $subscriptionPlan->price ?? '') }}" class="form-control">
    @error('price') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="billing_cycle" class="form-label">Billing Cycle</label>
    <input type="text" id="billing_cycle" name="billing_cycle" value="{{ old('billing_cycle', $subscriptionPlan->billing_cycle ?? '') }}" class="form-control">
    @error('billing_cycle') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="is_active" class="form-label">Is Active</label>
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" id="is_active" name="is_active" value="1" {{ (bool) old('is_active', $subscriptionPlan->is_active ?? false) ? 'checked' : '' }}>
    @error('is_active') <div class="text-danger">{{ $message }}</div> @enderror
</div>