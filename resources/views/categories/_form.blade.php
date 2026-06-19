<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $category->name ?? '') }}" class="form-control">
    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
</div>