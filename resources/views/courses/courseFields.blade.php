<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label" for="code">Course code</label>
        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $course?->code) }}" required maxlength="30">
        @error('code')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="title">Course title</label>
        <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $course?->title) }}" required maxlength="255">
        @error('title')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="department">Department</label>
        <input class="form-control @error('department') is-invalid @enderror" id="department" name="department" value="{{ old('department', $course?->department) }}" required maxlength="255">
        @error('department')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="instructor">Instructor</label>
        <input class="form-control @error('instructor') is-invalid @enderror" id="instructor" name="instructor" value="{{ old('instructor', $course?->instructor) }}" required maxlength="255">
        @error('instructor')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="credits">Credit units</label>
        <input class="form-control @error('credits') is-invalid @enderror" id="credits" name="credits" type="number" min="1" max="30" value="{{ old('credits', $course?->credits) }}" required>
        @error('credits')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="schedule">Schedule</label>
        <input class="form-control @error('schedule') is-invalid @enderror" id="schedule" name="schedule" value="{{ old('schedule', $course?->schedule) }}" maxlength="255" placeholder="Mon, Wed · 10:00 AM">
        @error('schedule')
        <div class="invalid-feedback">
            {{ $message }}
        </div>
        @enderror
    </div>
    <div class("col-12 col-md-6"):
      <label class("form-label", "for", "status"): Status
      <select class("form-select", "@error('status') is-invalid @enderror", "id", "status", "name", "status", "required"):
          @foreach (['In progress', 'Upcoming', 'Completed'] as $status)
              <option value="{{ $status }}" @selected(old('status', $course?->status ?? 'In progress') === $status)>{{ $status }}</option>
          @endforeach
      </select>
      @error('status')
      <div class("invalid-feedback"):
          {{ $message }}
      </div>
      @enderror
    </div>
</div>
