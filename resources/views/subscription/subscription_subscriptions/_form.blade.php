<div class="mb-3">
    <label for="subscriber_id" class="form-label">Subscriber Id</label>
    <select id="subscriber_id" name="subscriber_id" class="form-control">
        <option value="">Select Subscriber Id</option>
        @foreach($subscriberOptions ?? [] as $option)
            <option value="{{ $option->id }}" {{ (string) old('subscriber_id', $subscriptionSubscription->subscriber_id ?? '') === (string) $option->id ? 'selected' : '' }}>
                {{ $option->name ?? $option->id }}
            </option>
        @endforeach
    </select>
    @error('subscriber_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="plan_id" class="form-label">Plan Id</label>
    <select id="plan_id" name="plan_id" class="form-control">
        <option value="">Select Plan Id</option>
        @foreach($planOptions ?? [] as $option)
            <option value="{{ $option->id }}" {{ (string) old('plan_id', $subscriptionSubscription->plan_id ?? '') === (string) $option->id ? 'selected' : '' }}>
                {{ $option->name ?? $option->id }}
            </option>
        @endforeach
    </select>
    @error('plan_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="starts_at" class="form-label">Starts At</label>
    <input type="date" id="starts_at" name="starts_at" value="{{ old('starts_at', $subscriptionSubscription->starts_at ?? '') }}" class="form-control">
    @error('starts_at') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="ends_at" class="form-label">Ends At</label>
    <input type="date" id="ends_at" name="ends_at" value="{{ old('ends_at', $subscriptionSubscription->ends_at ?? '') }}" class="form-control">
    @error('ends_at') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="status" class="form-label">Status</label>
    <input type="text" id="status" name="status" value="{{ old('status', $subscriptionSubscription->status ?? '') }}" class="form-control">
    @error('status') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input type="text" id="name" name="name" value="{{ old('name', $subscriptionSubscription->name ?? '') }}" class="form-control">
    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
</div>