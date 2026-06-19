<div class="mb-3">
    <label for="category_id" class="form-label">Category Id</label>
    <select id="category_id" name="category_id" class="form-control">
        <option value="">Select Category Id</option>
        @foreach($categoryOptions ?? [] as $option)
            <option value="{{ $option->id }}" {{ (string) old('category_id', $productProduct->category_id ?? '') === (string) $option->id ? 'selected' : '' }}>
                {{ $option->name ?? $option->id }}
            </option>
        @endforeach
    </select>
    @error('category_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $productProduct->name ?? '') }}" class="form-control">
    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="sku" class="form-label">Sku</label>
    <input type="text" id="sku" name="sku" value="{{ old('sku', $productProduct->sku ?? '') }}" class="form-control">
    @error('sku') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="price" class="form-label">Price</label>
    <input type="number" id="price" name="price" value="{{ old('price', $productProduct->price ?? '') }}" class="form-control">
    @error('price') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="is_active" class="form-label">Is Active</label>
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" id="is_active" name="is_active" value="1" {{ (bool) old('is_active', $productProduct->is_active ?? false) ? 'checked' : '' }}>
    @error('is_active') <div class="text-danger">{{ $message }}</div> @enderror
</div>