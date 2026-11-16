@extends('layout.layout')

@section('title', 'Tests & examinations')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">MY STUDY</p>
        <h2>Tests &amp; examinations</h2>
        <p class="page-description">Scheduled assessments for your registered modules.</p>
    </div>
</section>

@forelse ($assessments as $assessment)
    @php($submission = $assessment->submissions->first())
    @php($deadline = $assessment->submissionDeadline())
    @php($status = $submission ? ($deadline && $submission->submitted_at?->gt($deadline) ? 'Late' : 'Submitted') : ($deadline && $deadline->isPast() ? 'Closed' : ($assessment->hasStarted() ? 'Available' : 'Scheduled')))
    <article class="card academic-table-card mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <p class="eyebrow mb-1">{{ $assessment->module->code }} · {{ $assessment->module->title }}</p>
                <h3 class="h5 mb-1">{{ $assessment->name }}</h3>
                <p class="text-secondary mb-0">
                    {{ str($assessment->assessment_type)->replace('_', ' ')->title() }}
                    · {{ $assessment->session->name }}
                    @if ($assessment->starts_at)
                        · Opens {{ $assessment->starts_at->format('M j, Y H:i') }}
                    @endif
                    @if ($deadline)
                        · Closes {{ $deadline->format('M j, Y H:i') }}
                    @endif
                </p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge {{ $status === 'Available' ? 'text-bg-primary' : ($status === 'Submitted' ? 'text-bg-success' : 'text-bg-secondary') }}">{{ $status }}</span>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('student-assessments.show', $assessment) }}" data-page-link>Open assessment</a>
            </div>
        </div>
    </article>
@empty
    <section class="card"><div class="card-body text-secondary">Assessments for your registered modules will appear here when they are published.</div></section>
@endforelse
@endsection
