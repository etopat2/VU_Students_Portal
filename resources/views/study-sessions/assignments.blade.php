@extends('layout.layout')

@section('title', 'Study assignments')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">STRATEGIC ENROLMENT</p>
        <h2>Study assignments</h2>
        <p class="page-description">Administrators can reassign a student’s session or program when institutional needs change. Each reassignment is recorded in the change history.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('study-sessions.index') }}" data-page-link>Manage sessions</a>
</section>

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Student</th><th>Current assignment</th><th>New session</th><th>New program</th><th>Strategic reason</th><th></th></tr></thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td><strong>{{ $student->name }}</strong><span class="d-block text-secondary small">{{ $student->student_number }} · {{ $student->primaryCourse?->title ?? $student->programme }}</span>@if($student->is_in_service_teacher)<span class="status-badge status-active mt-1">In-service teacher</span>@endif</td>
                        <td><span class="d-block">{{ $student->studySession?->name ?? 'No session' }}</span><span class="d-block text-secondary small">{{ $student->studyProgram?->name ?? 'No program' }}</span></td>
                        <td colspan="4">
                            <form action="{{ route('study-sessions.assign', $student) }}" method="POST" class="row g-2 align-items-end">
                                @csrf
                                @method('PUT')
                                <div class="col-12 col-lg-3">
                                    <label class="form-label small mb-1" for="study_session_{{ $student->id }}">Session</label>
                                    <select class="form-select form-select-sm" id="study_session_{{ $student->id }}" name="study_session_id">
                                        <option value="">Keep current</option>
                                        @foreach ($studySessions as $studySession)
                                            @if ($studySession->audience === 'all_students' || $student->is_in_service_teacher)
                                                <option value="{{ $studySession->id }}">{{ $studySession->name }}{{ $studySession->audience === 'in_service_teachers' ? ' · In-service' : '' }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-lg-3">
                                    <label class="form-label small mb-1" for="study_program_{{ $student->id }}">Program</label>
                                    <select class="form-select form-select-sm" id="study_program_{{ $student->id }}" name="study_program_id">
                                        <option value="">Keep current</option>
                                        @foreach ($student->primaryCourse?->studyPrograms ?? [] as $studyProgram)
                                            @if ($studyProgram->is_active)
                                                <option value="{{ $studyProgram->id }}">{{ $studyProgram->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <label class="form-label small mb-1" for="reason_{{ $student->id }}">Reason</label>
                                    <input class="form-control form-control-sm" id="reason_{{ $student->id }}" name="reason" required minlength="10" maxlength="1000" placeholder="Strategic reason">
                                </div>
                                <div class="col-12 col-lg-2"><button class="btn btn-sm btn-primary w-100" type="submit">Apply</button></div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="6">No student records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $students->links() }}</div>
</section>
@endsection
