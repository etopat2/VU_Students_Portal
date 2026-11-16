@extends('layout.layout')

@section('title', 'My study assignment')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">MY STUDY</p>
        <h2>My study session and program</h2>
        <p class="page-description">Request a move to another session or an eligible study program. A registrar or administrator will review it.</p>
    </div>
</section>

<section class="row g-3 mb-4" aria-label="Current study assignment">
    <div class="col-12 col-md-6">
        <article class="card dashboard-stat-card h-100"><div class="card-body">
            <p class="academic-stat-label">Current study session</p>
            <p class="academic-stat-value">{{ $student->studySession?->name ?? 'Not assigned' }}</p>
            <p class="academic-stat-caption">{{ $student->studySession?->audience === 'in_service_teachers' ? 'In-service teachers' : 'Study schedule' }}</p>
        </div></article>
    </div>
    <div class="col-12 col-md-6">
        <article class="card dashboard-stat-card h-100"><div class="card-body">
            <p class="academic-stat-label">Current study program</p>
            <p class="academic-stat-value">{{ $student->studyProgram?->name ?? 'Not assigned' }}</p>
            <p class="academic-stat-caption">{{ $student->primaryCourse?->title ?? $student->programme }}</p>
        </div></article>
    </div>
</section>

@if ($pendingRequest)
    <section class="alert alert-info" role="status">
        You have a change request awaiting review, submitted {{ $pendingRequest->created_at->format('M j, Y') }}.
    </section>
@else
    <section class="card academic-form-card mb-4">
        <div class="card-body">
            <h3 class="card-title h5 mb-1">Request a change</h3>
            <p class="text-secondary small mb-3">Choose a new session, a different program, or both. Leave an unchanged field blank.</p>
            <form action="{{ route('student.study-session.requests.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="study_session_id">Requested study session</label>
                        <select class="form-select @error('study_session_id') is-invalid @enderror" id="study_session_id" name="study_session_id">
                            <option value="">No session change</option>
                            @foreach ($studySessions as $studySession)
                                <option value="{{ $studySession->id }}" @selected((string) old('study_session_id') === (string) $studySession->id)>{{ $studySession->name }}{{ $studySession->audience === 'in_service_teachers' ? ' · In-service teachers' : '' }}</option>
                            @endforeach
                        </select>
                        @error('study_session_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="study_program_id">Requested study program</label>
                        <select class="form-select @error('study_program_id') is-invalid @enderror" id="study_program_id" name="study_program_id">
                            <option value="">No program change</option>
                            @foreach ($studyPrograms as $studyProgram)
                                <option value="{{ $studyProgram->id }}" @selected((string) old('study_program_id') === (string) $studyProgram->id)>{{ $studyProgram->name }}</option>
                            @endforeach
                        </select>
                        @error('study_program_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="reason">Reason for request</label>
                        <textarea class="form-control @error('reason') is-invalid @enderror" id="reason" name="reason" rows="3" minlength="10" maxlength="1000" required>{{ old('reason') }}</textarea>
                        @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                @error('request')<div class="text-danger small mt-3">{{ $message }}</div>@enderror
                <div class="form-actions"><button class="btn btn-primary" type="submit">Send for review</button></div>
            </form>
        </div>
    </section>
@endif

<section class="card academic-table-card">
    <div class="card-body">
        <h3 class="card-title h5">Change history</h3>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Date</th><th>Session</th><th>Program</th><th>Reason</th><th>Status</th><th>Review note</th></tr></thead>
                <tbody>
                    @forelse ($history as $item)
                        <tr>
                            <td>{{ $item->created_at->format('M j, Y') }}</td>
                            <td>{{ $item->currentStudySession?->name ?? '—' }} → {{ $item->requestedStudySession?->name ?? ($item->requested_study_session_id ? 'Unavailable' : 'No change') }}</td>
                            <td>{{ $item->currentStudyProgram?->name ?? '—' }} → {{ $item->requestedStudyProgram?->name ?? ($item->requested_study_program_id ? 'Unavailable' : 'No change') }}</td>
                            <td>{{ $item->reason }}</td>
                            <td><span class="status-badge {{ $item->status === 'approved' ? 'status-active' : 'status-muted' }}">{{ ucfirst($item->status) }}</span></td>
                            <td>{{ $item->review_notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="6">No session or program change requests yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
