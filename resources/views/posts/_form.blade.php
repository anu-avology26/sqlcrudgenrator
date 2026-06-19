<div class="mb-3">
    <label for="user_id" class="form-label">User Id</label>
    <input type="number" id="user_id" name="user_id" value="{{ old('user_id', $post->user_id ?? '') }}" class="form-control">
    @error('user_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="category_id" class="form-label">Category Id</label>
    <input type="number" id="category_id" name="category_id" value="{{ old('category_id', $post->category_id ?? '') }}" class="form-control">
    @error('category_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="title" class="form-label">Title</label>
    <input type="text" id="title" name="title" value="{{ old('title', $post->title ?? '') }}" class="form-control">
    @error('title') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="body" class="form-label">Body</label>
    <textarea id="body" name="body" class="form-control">{{ old('body', $post->body ?? '') }}</textarea>
    @error('body') <div class="text-danger">{{ $message }}</div> @enderror
</div>