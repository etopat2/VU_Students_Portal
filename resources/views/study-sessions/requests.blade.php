@extends('layout.layout')

@section('title', 'Study change requests')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">STUDENT SERVICES</p>
        <h2>Session and program change requests</h2>
        <p class="page-description">Review student requests and approve or decline proposed study assignment changes.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('study-sessions.index') }}" data-page-link>Study sessions</a>
</section>

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Student</th><th>Current</th><th>Requested</th><th>Reason</th><th>Status</th><th>Review</th></tr></thead>
            <tbody>
                @forelse ($requests as $changeRequest)
                    <tr>
                        <td><strong>{{ $changeRequest->student->name }}</strong><span class="d-block text-secondary small">{{ $changeRequest->student->student_number }} · {{ $changeRequest->student->primaryCourse?->title }}</span><span class="d-block text-secondary small">Requested by {{ $changeRequest->requester?->name ?? 'Former account' }} · {{ $changeRequest->created_at->format('M j, Y') }}</span></td>
                        <td>{{ $changeRequest->currentStudySession?->name ?? 'No session' }}<span class="d-block text-secondary small">{{ $changeRequest->currentStudyProgram?->name ?? 'No program' }}</span></td>
                        <td>{{ $changeRequest->requested_study_session_id ? ($changeRequest->requestedStudySession?->name ?? 'Unavailable') : 'No session change' }}<span class="d-block text-secondary small">{{ $changeRequest->requested_study_program_id ? ($changeRequest->requestedStudyProgram?->name ?? 'Unavailable') : 'No program change' }}</span></td>
                        <td>{{ $changeRequest->reason }}</td>
                        <td><span class="status-badge {{ $changeRequest->status === 'approved' ? 'status-active' : ($changeRequest->status === 'pending' ? 'status-muted' : 'status-muted') }}">{{ ucfirst($changeRequest->status) }}</span></td>
                        <td>
                            @if ($changeRequest->status === 'pending')
                                <form action="{{ route('study-session-requests.review', $changeRequest) }}" method="POST" class="d-grid gap-2">
                                    @csrf
                                    @method('PUT')
                                    <textarea class="form-control form-control-sm" name="review_notes" rows="2" maxlength="1000" placeholder="Review note (required to decline)"></textarea>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-success" type="submit" name="status" value="approved">Approve</button>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" name="status" value="rejected">Decline</button>
                                    </div>
                                </form>
                            @else
                                <span class="small">{{ $changeRequest->reviewer?->name ?? 'Reviewed' }}{{ $changeRequest->reviewed_at ? ' · '.$changeRequest->reviewed_at->format('M j, Y') : '' }}</span>
                                @if ($changeRequest->review_notes)<span class="d-block text-secondary small">{{ $changeRequest->review_notes }}</span>@endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="table-empty" colspan="6">No study change requests yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $requests->links() }}</div>
</section>
@endsection
