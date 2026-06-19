<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $subscriptionSubscriber->name ?? '') }}" class="form-control">
    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" id="email" name="email" value="{{ old('email', $subscriptionSubscriber->email ?? '') }}" class="form-control">
    @error('email') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Phone</label>
    <input type="text" id="phone" name="phone" value="{{ old('phone', $subscriptionSubscriber->phone ?? '') }}" class="form-control">
    @error('phone') <div class="text-danger">{{ $message }}</div> @enderror
</div>