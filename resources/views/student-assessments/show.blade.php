@extends('layout.layout')

@section('title', $assessment->name)

@section('content')
@php($deadline = $assessment->submissionDeadline())
<section class="page-intro d-flex flex-wrap align-items-start justify-content-between gap-3">
    <div>
        <p class="eyebrow">ASSESSMENT</p>
        <h2>{{ $assessment->name }}</h2>
        <p class="page-description">{{ $assessment->module->code }} · {{ $assessment->module->title }} · {{ $assessment->session->name }}</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('student-assessments.index') }}" data-page-link>Back to assessments</a>
</section>

<section class="card academic-table-card mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between gap-3">
        <div>
            <p class="text-secondary mb-1">Assessment type</p>
            <strong>{{ str($assessment->assessment_type)->replace('_', ' ')->title() }}</strong>
        </div>
        <div>
            <p class="text-secondary mb-1">Maximum score</p>
            <strong>{{ $assessment->max_score }}</strong>
        </div>
        <div>
            <p class="text-secondary mb-1">Available from</p>
            <strong>{{ $assessment->starts_at?->format('M j, Y H:i') ?? 'Now' }}</strong>
        </div>
        <div>
            <p class="text-secondary mb-1">Submission deadline</p>
            <strong>{{ $deadline?->format('M j, Y H:i') ?? 'No deadline set' }}</strong>
        </div>
    </div>
</section>

<section class="card academic-table-card mb-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h3 class="h5 mb-1">Assessment questions</h3>
            <p class="text-secondary mb-0">
                @if ($assessment->question_file_original_name)
                    {{ $assessment->question_file_original_name }}
                @else
                    Your lecturer has not attached a question document.
                @endif
            </p>
        </div>
        @if ($assessment->question_file_path && $assessment->submissionWindowIsOpen())
            <a class="btn btn-outline-primary" href="{{ route('student-assessments.question', $assessment) }}>Download questions</a>
        @elseif ($assessment->question_file_path && ! $assessment->hasStarted())
            <span class="badge text-bg-secondary">Questions available at start time</span>
        @elseif ($assessment->question_file_path)
            <span class="badge text-bg-secondary">Question access is closed</span>
        @endif
    </div>
</section>

<section class="card academic-table-card">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
            <div>
                <h3 class="h5 mb-1">Your answer</h3>
                <p class="text-secondary mb-0">
                    @if ($submission?->submitted_at)
                        Last submitted {{ $submission->submitted_at->format('M j, Y H:i') }}. You may revise it until the deadline.
                    @else
                        Write your response below or attach an answer sheet. PDF, DOCX, XLSX, JPG and PNG files up to 50 MB are accepted.
                    @endif
                </p>
            </div>
            @if ($submission?->answer_file_path)
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('student-assessments.answer', $assessment) }}">Download your attached answer</a>
            @endif
        </div>

        @if ($assessment->submissionWindowIsOpen())
            <form action="{{ route('student-assessments.submissions.store', $assessment) }}" method="POST" enctype="multipart/form-data" data-assessment-editor-form>
                @csrf
                <div class="answer-editor mb-3">
                    <div class="answer-editor-toolbar" role="toolbar" aria-label="Answer formatting">
                        <button class="btn btn-sm btn-light" type="button" data-editor-command="bold" aria-label="Bold"><strong>B</strong></button>
                        <button class="btn btn-sm btn-light" type="button" data-editor-command="italic" aria-label="Italic"><em>I</em></button>
                        <button class="btn btn-sm btn-light" type="button" data-editor-command="underline" aria-label="Underline"><u>U</u></button>
                        <button class="btn btn-sm btn-light" type="button" data-editor-command="insertUnorderedList" aria-label="Bulleted list"><i class="fa fa-list-ul" aria-hidden="true"></i></button>
                        <button class="btn btn-sm btn-light" type="button" data-editor-command="insertOrderedList" aria-label="Numbered list"><i class="fa fa-list-ol" aria-hidden="true"></i></button>
                    </div>
                    <div class="answer-editor-content @error('answer_content') is-invalid @enderror" contenteditable="true" role="textbox" aria-label="Write your answer" aria-multiline="true" data-answer-editor>{!! \App\Support\AssessmentAnswerContent::sanitize((string) old('answer_content', $submission?->answer_content ?? '')) !!}</div>
                    <input type="hidden" name="answer_content" data-answer-content>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="answer_file">Attach an answer sheet (optional)</label>
                    <input class="form-control @error('answer_file') is-invalid @enderror" id="answer_file" name="answer_file" type="file" accept=".pdf,.docx,.xlsx,.jpg,.jpeg,.png">
                </div>
                <button class="btn btn-primary" type="submit">{{ $submission ? 'Save and resubmit answer' : 'Submit answer' }}</button>
            </form>
        @else
            <div class="alert alert-light border mb-0" role="status">
                @if (! $assessment->hasStarted())
                    This assessment has not opened yet. You can submit an answer after the start time.
                @else
                    The submission window is closed. Answers can no longer be changed.
                @endif
            </div>
            @if ($submission?->answer_content)
                <div class="answer-submitted-content mt-3">{!! \App\Support\AssessmentAnswerContent::sanitize($submission->answer_content) !!}</div>
            @endif
        @endif
    </div>
</section>
@endsection
