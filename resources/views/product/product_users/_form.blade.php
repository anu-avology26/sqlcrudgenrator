<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $productUser->name ?? '') }}" class="form-control">
    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" id="email" name="email" value="{{ old('email', $productUser->email ?? '') }}" class="form-control">
    @error('email') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Phone</label>
    <input type="text" id="phone" name="phone" value="{{ old('phone', $productUser->phone ?? '') }}" class="form-control">
    @error('phone') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="is_active" class="form-label">Is Active</label>
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" id="is_active" name="is_active" value="1" {{ (bool) old('is_active', $productUser->is_active ?? false) ? 'checked' : '' }}>
    @error('is_active') <div class="text-danger">{{ $message }}</div> @enderror
</div>