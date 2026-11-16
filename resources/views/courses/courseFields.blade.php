<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label" for="code">Course code</label>
        <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $course?->code) }}" required maxlength="30">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="title">Course title</label>
        <input class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $course?->title) }}" required maxlength="255">
    </div>
    @php
        $selectedDepartment = old('department_id', $course?->department_id);
        $selectedFaculty = old('faculty_id', $course?->departmentRecord?->faculty_id);
        $selectedStudyProgramIds = old(
            'study_program_ids',
            $course?->studyPrograms->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [],
        );
    @endphp
    <div class="col-12 col-md-6">
        <label class="form-label" for="faculty_id">Faculty</label>
        <select class="form-select @error('faculty_id') is-invalid @enderror" id="faculty_id" name="faculty_id" required data-faculty-filter>
            <option value="">Select a faculty</option>
            @foreach ($faculties as $faculty)
                <option value="{{ $faculty->id }}" @selected((string) $selectedFaculty === (string) $faculty->id)>{{ $faculty->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="department_id">Department</label>
        <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required data-department-filter>
            <option value="">Select a department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" data-faculty-id="{{ $department->faculty_id }}" @selected((string) $selectedDepartment === (string) $department->id)>{{ $department->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="lecturer_id">Instructor / Head of course</label>
        <select class="form-select @error('lecturer_id') is-invalid @enderror" id="lecturer_id" name="lecturer_id" required data-lecturer-filter>
            <option value="">Select a lecturer</option>
            @foreach ($lecturers as $lecturer)
                <option value="{{ $lecturer->id }}" data-department-id="{{ $lecturer->department_id }}" @selected((string) old('lecturer_id', $course?->lecturer_id) === (string) $lecturer->id)>{{ $lecturer->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label class="form-label d-block">Study programs offering this course</label>
        <div class="d-flex flex-wrap gap-3">
            @foreach ($studyPrograms as $studyProgram)
                <label class="form-check">
                    <input class="form-check-input" type="checkbox" name="study_program_ids[]" value="{{ $studyProgram->id }}" @checked(in_array((string) $studyProgram->id, array_map('strval', $selectedStudyProgramIds), true))>
                    <span class="form-check-label">{{ $studyProgram->name }}</span>
                </label>
            @endforeach
        </div>
    </div>
    <div class="col-12">
        <label class="form-label d-block">Modules taught in this course</label>
        <p class="form-text">Modules can be shared with other courses across departments and faculties.</p>
        @php
            $selectedModuleIds = old(
                'module_ids',
                $course?->modules->pluck('id')->map(fn ($id) => (string) $id)->all() ?? [],
            );
        @endphp
        <div class="row g-2">
            @forelse ($modules as $module)
                <div class="col-12 col-md-6 col-xl-4">
                    <label class="form-check border rounded p-3 h-100">
                        <input class="form-check-input" type="checkbox" name="module_ids[]" value="{{ $module->id }}" @checked(in_array((string) $module->id, array_map('strval', $selectedModuleIds), true))>
                        <span class="form-check-label d-block">
                            <strong>{{ $module->code }} — {{ $module->title }}</strong>
                            <small class="d-block text-secondary">{{ $module->department->faculty->name }} · {{ $module->department->name }} · Year {{ $module->year_level }}, Semester {{ $module->session_number }}</small>
                        </span>
                    </label>
                </div>
            @empty
                <div class="col-12"><p class="text-secondary mb-0">No modules have been created yet. You can add them from the Modules page.</p></div>
            @endforelse
        </div>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="credits">Credit units</label>
        <input class="form-control @error('credits') is-invalid @enderror" id="credits" name="credits" type="number" min="1" max="30" value="{{ old('credits', $course?->credits) }}" required>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="schedule">Schedule</label>
        <input class="form-control @error('schedule') is-invalid @enderror" id="schedule" name="schedule" value="{{ old('schedule', $course?->schedule) }}" maxlength="255" placeholder="Mon, Wed · 10:00 AM">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach (['In progress', 'Upcoming', 'Completed'] as $status)
                <option value="{{ $status }}" @selected(old('status', $course?->status ?? 'In progress') === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
</div>
