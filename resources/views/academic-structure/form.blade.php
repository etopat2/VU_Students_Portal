@extends('layout.layout')

@section('title', $title)

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">ACADEMIC SETUP</p>
        <h2>{{ $record ? 'Edit' : 'Add' }} {{ Str::singular($title) }}</h2>
        <p class="page-description">Enter the academic structure details.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('academic-structure.index', ['type' => $type]) }}" data-page-link>Back to {{ $title }}</a>
</section>

<section class="card academic-form-card">
    <div class="card-body">
        <form action="{{ $record ? route('academic-structure.update', ['type' => $type, 'record' => $record->id]) : route('academic-structure.store', ['type' => $type]) }}" method="POST">
            @csrf
            @if ($record)
                @method('PUT')
            @endif

            @if ($type === 'faculties')
                <div class="mb-3">
                    <label class="form-label" for="institution_id">Institution</label>
                    <select class="form-select @error('institution_id') is-invalid @enderror" name="institution_id" id="institution_id" required>
                        <option value="">Select an institution</option>
                        @foreach ($institutions as $institution)
                            <option value="{{ $institution->id }}" @selected((string) old('institution_id', $record?->institution_id) === (string) $institution->id)>{{ $institution->name }}</option>
                        @endforeach
                    </select>
                </div>
                @include('academic-structure.name-code-fields', ['record' => $record])
            @elseif ($type === 'departments')
                <div class="mb-3">
                    <label class="form-label" for="faculty_id">Faculty</label>
                    <select class="form-select @error('faculty_id') is-invalid @enderror" name="faculty_id" id="faculty_id" required>
                        <option value="">Select a faculty</option>
                        @foreach ($faculties as $faculty)
                            <option value="{{ $faculty->id }}" @selected((string) old('faculty_id', $record?->faculty_id) === (string) $faculty->id)>{{ $faculty->name }}</option>
                        @endforeach
                    </select>
                </div>
                @include('academic-structure.name-code-fields', ['record' => $record])
            @elseif ($type === 'study-programs')
                <div class="mb-3">
                    <label class="form-label" for="name">Study program</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $record?->name) }}" maxlength="50" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="description">Description</label>
                    <input class="form-control @error('description') is-invalid @enderror" id="description" name="description" value="{{ old('description', $record?->description) }}" maxlength="255">
                </div>
                <input type="hidden" name="is_active" value="0">
                <div class="form-check mb-3">
                    <input class="form-check-input" id="is_active" name="is_active" value="1" type="checkbox" @checked((bool) old('is_active', $record?->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Available for student enrollment</label>
                </div>
            @elseif ($type === 'modules')
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="code">Module code</label>
                        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $record?->code) }}" maxlength="30" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="title">Module title</label>
                        <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $record?->title) }}" maxlength="255" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="department_id">Department</label>
                        <select class="form-select @error('department_id') is-invalid @enderror" name="department_id" id="department_id" required>
                            <option value="">Select a department</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" @selected((string) old('department_id', $record?->department_id) === (string) $department->id)>{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="credit_units">Credit units</label>
                        <input class="form-control @error('credit_units') is-invalid @enderror" id="credit_units" name="credit_units" type="number" min="1" max="30" value="{{ old('credit_units', $record?->credit_units ?? 4) }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="year_level">Year of study</label>
                        <select class="form-select @error('year_level') is-invalid @enderror" name="year_level" id="year_level" required>
                            @foreach (range(1, 4) as $yearLevel)
                                <option value="{{ $yearLevel }}" @selected((string) old('year_level', $record?->year_level ?? 1) === (string) $yearLevel)>Year {{ $yearLevel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="session_number">Semester</label>
                        <select class="form-select @error('session_number') is-invalid @enderror" name="session_number" id="session_number" required>
                            @foreach ([1 => 'Semester 1', 2 => 'Semester 2'] as $number => $label)
                                <option value="{{ $number }}" @selected((string) old('session_number', $record?->session_number ?? 1) === (string) $number)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label d-block">Courses offering this module <span class="text-secondary fw-normal">(optional)</span></label>
                        <p class="form-text">You can create the module first and assign it to one or more courses later from the course form.</p>
                        @php($selectedCourseIds = array_map('strval', old('course_ids', $record?->courses->pluck('id')->all() ?? [])))
                        <div class="d-flex flex-wrap gap-3">
                            @forelse ($courses as $course)
                                <label class="form-check">
                                    <input class="form-check-input" type="checkbox" name="course_ids[]" value="{{ $course->id }}" @checked(in_array((string) $course->id, $selectedCourseIds, true))>
                                    <span class="form-check-label">{{ $course->code }} — {{ $course->title }}</span>
                                </label>
                            @empty
                                <p class="text-secondary mb-0">No courses have been created yet. You can assign this module later from a course form.</p>
                            @endforelse
                        </div>
                        @error('course_ids')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                        @error('course_ids.*')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            @endif

            <div class="form-actions">
                <a class="btn btn-outline-secondary" href="{{ route('academic-structure.index', ['type' => $type]) }}" data-page-link>Cancel</a>
                <button class="btn btn-primary" type="submit">{{ $record ? 'Save changes' : 'Create record' }}</button>
            </div>
        </form>
    </div>
</section>
@endsection
