<div class="mb-3">
    <label class="form-label" for="name">Name</label>
    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $record?->name) }}" maxlength="255" required>
</div>
<div class="mb-3">
    <label class="form-label" for="code">Code</label>
    <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $record?->code) }}" maxlength="30" required>
</div>
