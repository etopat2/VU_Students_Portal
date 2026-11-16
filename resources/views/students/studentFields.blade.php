<div class="row g-3">
    <div class="col-12 col-md-6">
        <label class="form-label" for="student_number">Student ID</label>
        <input class="form-control @error('student_number') is-invalid @enderror" id="student_number" name="student_number" value="{{ old('student_number', $student?->student_number) }}" required maxlength="50">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="name">Full name</label>
        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $student?->name) }}" required maxlength="255">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="email">Email address</label>
        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $student?->email) }}" required maxlength="255">
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="study_session_id">Study session</label>
        <select class="form-select @error('study_session_id') is-invalid @enderror" id="study_session_id" name="study_session_id" required>
            <option value="">Select a study session</option>
            @foreach ($studySessions as $studySession)
                <option value="{{ $studySession->id }}" data-in-service-only="{{ $studySession->audience === 'in_service_teachers' ? 'true' : 'false' }}" @selected((string) old('study_session_id', $student?->study_session_id) === (string) $studySession->id)>{{ $studySession->name }}{{ $studySession->audience === 'in_service_teachers' ? ' · In-service teachers' : '' }}</option>
            @endforeach
        </select>
        @error('study_session_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="study_program_id">Study program</label>
        <select class="form-select @error('study_program_id') is-invalid @enderror" id="study_program_id" name="study_program_id" required data-course-program-filter>
            <option value="">Select a study program</option>
            @foreach ($studyPrograms as $studyProgram)
                <option value="{{ $studyProgram->id }}" @selected((string) old('study_program_id', $student?->study_program_id) === (string) $studyProgram->id)>{{ $studyProgram->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="course_id">Course / award</label>
        <select class="form-select @error('course_id') is-invalid @enderror" id="course_id" name="course_id" required data-academic-course-filter>
            <option value="">Select a course</option>
            @foreach ($courses as $courseOption)
                <option value="{{ $courseOption->id }}" data-study-programs="{{ $courseOption->studyPrograms->pluck('id')->implode(',') }}" @selected((string) old('course_id', $student?->course_id) === (string) $courseOption->id)>{{ $courseOption->code }} — {{ $courseOption->title }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="academic_year_id">Year of study</label>
        <select class="form-select @error('academic_year_id') is-invalid @enderror" id="academic_year_id" name="academic_year_id" required data-academic-year-filter>
            <option value="">Select a year of study</option>
            @foreach ($academicYears as $academicYear)
                <option value="{{ $academicYear->id }}" data-course-id="{{ $academicYear->course_id }}" @selected((string) old('academic_year_id', $student?->academic_year_id) === (string) $academicYear->id)>{{ $academicYear->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6">
        <label class="form-label" for="status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
            @foreach (['Active', 'On leave', 'Graduated'] as $status)
                <option value="{{ $status }}" @selected(old('status', $student?->status ?? 'Active') === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-md-6">
        <input type="hidden" name="is_in_service_teacher" value="0">
        <label class="form-check mt-md-4">
            <input class="form-check-input" type="checkbox" id="is_in_service_teacher" name="is_in_service_teacher" value="1" @checked((bool) old('is_in_service_teacher', $student?->is_in_service_teacher ?? false))>
            <span class="form-check-label">Student is an in-service teacher</span>
        </label>
    </div>
    @if ($student)
        <div class="col-12">
            <label class="form-label" for="assignment_reason">Reason for session or program change <span class="text-secondary fw-normal">(required when changing either)</span></label>
            <textarea class="form-control @error('assignment_reason') is-invalid @enderror" id="assignment_reason" name="assignment_reason" rows="2" maxlength="1000">{{ old('assignment_reason') }}</textarea>
            @error('assignment_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif
</div>
