<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label" for="student_number">Student ID</label>
        <input class="form-control @error('student_number') is-invalid @enderror" id="student_number" name="student_number" value="{{ old('student_number', $student?->student_number) }}" required maxlength="50">
        @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="name">Full name</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $student?->name) }}" required maxlength="255">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="email">Email address</label>
        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $student?->email) }}" required maxlength="255">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="programme">Programme</label>
        <input class="form-control @error('programme') is-invalid @enderror" id="programme" name="programme" value="{{ old('programme', $student?->programme) }}" required maxlength="255">
        @error('programme')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="year">Year of study</label>
        <input class="form-control @error('year') is-invalid @enderror" id="year" name="year" value="{{ old('year', $student?->year) }}" placeholder="Year 1" required maxlength="50">
        @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach (['Active', 'On leave', 'Graduated'] as $status)
                <option value="{{ $status }}" @selected(old('status', $student?->status ?? 'Active') === $status)>{{ $status }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
