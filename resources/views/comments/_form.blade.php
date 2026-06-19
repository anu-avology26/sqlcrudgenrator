<div class="mb-3">
    <label for="post_id" class="form-label">Post Id</label>
    <input type="number" id="post_id" name="post_id" value="{{ old('post_id', $comment->post_id ?? '') }}" class="form-control">
    @error('post_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="user_id" class="form-label">User Id</label>
    <input type="number" id="user_id" name="user_id" value="{{ old('user_id', $comment->user_id ?? '') }}" class="form-control">
    @error('user_id') <div class="text-danger">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="comment" class="form-label">Comment</label>
    <textarea id="comment" name="comment" class="form-control">{{ old('comment', $comment->comment ?? '') }}</textarea>
    @error('comment') <div class="text-danger">{{ $message }}</div> @enderror
</div>