<div class="mb-3">
    <label for="user_id" class="form-label">User Id</label>
    <select id="user_id" name="user_id" class="form-control">
        <option value="">Select User Id</option>
        @foreach($userOptions ?? [] as $option)
            <option value="{{ $option->id }}" {{ (string) old('user_id', $productOrder->user_id ?? '') === (string) $option->id ? 'selected' : '' }}>
                {{ $option->name ?? $option->id }}
            </option>
        @endforeach
    </select>
    @error('user_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="product_id" class="form-label">Product Id</label>
    <select id="product_id" name="product_id" class="form-control">
        <option value="">Select Product Id</option>
        @foreach($productOptions ?? [] as $option)
            <option value="{{ $option->id }}" {{ (string) old('product_id', $productOrder->product_id ?? '') === (string) $option->id ? 'selected' : '' }}>
                {{ $option->name ?? $option->id }}
            </option>
        @endforeach
    </select>
    @error('product_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="quantity" class="form-label">Quantity</label>
    <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $productOrder->quantity ?? '') }}" class="form-control">
    @error('quantity') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="order_date" class="form-label">Order Date</label>
    <input type="date" id="order_date" name="order_date" value="{{ old('order_date', $productOrder->order_date ?? '') }}" class="form-control">
    @error('order_date') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <select id="status" name="status" class="form-control">

    </select>
    @error('status') <div class="text-danger">{{ $message }}</div> @enderror
</div>