@extends('layout.layout')

@section('title', 'Submission status')

@section('content')
<section class="page-intro d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <p class="eyebrow">ASSESSMENT REPORT</p>
        <h2>Submission status</h2>
        <p class="page-description">{{ $assessment->name }} · {{ $assessment->module->code }} · {{ $assessment->session->name }}</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('assessments.index') }}" data-page-link>Back to assessments</a>
</section>

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Student</th><th>Student number</th><th>Status</th><th>Submitted at</th><th>Response / answer sheet</th></tr></thead>
            <tbody>
                @forelse ($registrations as $registration)
                    @php($submission = $registration->assessmentSubmissions->first())
                    @php($deadline = $assessment->submissionDeadline())
                    @php($status = $submission ? ($deadline && $submission->submitted_at?->gt($deadline) ? 'Late' : 'Submitted') : ($deadline && $deadline->isPast() ? 'Missing' : 'Pending'))
                    <tr>
                        <td>{{ $registration->semesterRegistration->student->name }}</td>
                        <td>{{ $registration->semesterRegistration->student->student_number }}</td>
                        <td><span class="badge {{ $status === 'Submitted' ? 'text-bg-success' : ($status === 'Late' ? 'text-bg-warning' : 'text-bg-secondary') }}">{{ $status }}</span></td>
                        <td>{{ $submission?->submitted_at?->format('M j, Y H:i') ?? '—' }}</td>
                        <td>
                            @if ($submission?->answer_content)
                                <details class="mb-1">
                                    <summary>View online response</summary>
                                    <div class="answer-submitted-content mt-2">{!! \App\Support\AssessmentAnswerContent::sanitize($submission->answer_content) !!}</div>
                                </details>
                            @endif
                            @if ($submission?->answer_file_path)
                                <a href="{{ route('assessments.submissions.download', [$assessment, $submission]) }}">{{ $submission->answer_file_original_name }}</a>
                            @elseif ($submission?->answer_content)
                                <span class="text-secondary">Online response</span>
                            @else
                                <span class="text-secondary">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="5">No students are registered for this module and session.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($registrations->hasPages())
        <div class="card-body border-top">{{ $registrations->links() }}</div>
    @endif
</section>
@endsection
