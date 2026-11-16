@extends('layout.layout')

@section('title', 'Study sessions')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">ACADEMIC SETUP</p>
        <h2>Study sessions</h2>
        <p class="page-description">Manage Day, Weekend, Evening, and Holiday study schedules, including sessions for in-service teachers.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="{{ route('study-session-requests.index') }}" data-page-link>Change requests <span class="badge text-bg-primary">{{ $pendingRequestCount }}</span></a>
        @if (auth()->user()->isAdmin())
            <a class="btn btn-outline-secondary" href="{{ route('study-sessions.assignments') }}" data-page-link>Strategic assignments</a>
        @endif
        <a class="btn btn-primary" href="{{ route('study-sessions.create') }}" data-page-link>Add study session</a>
    </div>
</section>

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Session</th><th>Audience</th><th>Description</th><th>Students</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($sessions as $studySession)
                    <tr>
                        <td><strong>{{ $studySession->name }}</strong><span class="d-block text-secondary small">{{ $studySession->code }}</span></td>
                        <td>{{ $studySession->audience === 'in_service_teachers' ? 'In-service teachers' : 'All students' }}</td>
                        <td>{{ $studySession->description ?: '—' }}</td>
                        <td>{{ number_format($studySession->students_count) }}</td>
                        <td><span class="status-badge {{ $studySession->is_active ? 'status-active' : 'status-muted' }}">{{ $studySession->is_active ? 'Active' : 'Inactive' }}</span></td>
                        <td class="text-end text-nowrap">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('study-sessions.edit', $studySession) }}" data-page-link>Edit</a>
                            @if ($studySession->students_count === 0)
                                <form action="{{ route('study-sessions.destroy', $studySession) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this study session?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="6">No study sessions yet. Add Day, Weekend, Evening, or an in-service Holiday session.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $sessions->links() }}</div>
</section>
@endsection
