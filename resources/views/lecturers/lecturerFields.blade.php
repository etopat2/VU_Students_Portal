<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label" for="lecturer_number">Staff ID</label>
        <input class="form-control @error('lecturer_number') is-invalid @enderror" id="lecturer_number" name="lecturer_number" value="{{ old('lecturer_number', $lecturer?->lecturer_number) }}" required maxlength="50">
        @error('lecturer_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="name">Full name</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $lecturer?->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="email">Email address</label>
        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $lecturer?->email) }}" required maxlength="255">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="department">Department</label>
        <input class="form-control @error('department') is-invalid @enderror" id="department" name="department" value="{{ old('department', $lecturer?->department) }}" required maxlength="255">
        @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="qualification">Highest qualification</label>
        <input class="form-control @error('qualification') is-invalid @enderror" id="qualification" name="qualification" value="{{ old('qualification', $lecturer?->qualification) }}" required maxlength="255" placeholder="MSc Information Technology">
        @error('qualification')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="phone">Phone number <span class="text-secondary">(optional)</span></label>
        <input class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" type="tel" value="{{ old('phone', $lecturer?->phone) }}" maxlength="30">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach (['Active', 'On leave'] as $status)
                <option value="{{ $status }}" @selected(old('status', $lecturer?->status ?? 'Active') === $status)>{{ $status }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

