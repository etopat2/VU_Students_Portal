@extends('layout.layout')

@section('title', 'Tests & examinations')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">TEACHING</p>
        <h2>Tests &amp; examinations</h2>
        <p class="page-description">
            @if ($isRegistrar)
                Manage assessments for assigned modules and oversee result entry.
            @else
                Create assessments for modules assigned to you and enter results for registered students.
            @endif
        </p>
    </div>
</section>

@forelse ($allocations as $allocation)
    <section class="card academic-table-card mb-4">
        <div class="card-header bg-white">
            <h3 class="h5 mb-1">{{ $allocation->module->code }} · {{ $allocation->module->title }}</h3>
            <p class="text-secondary mb-0">{{ $allocation->session->academicYear->name }} · {{ $allocation->session->name }}</p>
        </div>
        <div class="card-body border-bottom">
            <h4 class="h6">Add assessment</h4>
            <form action="{{ route('assessments.store', ['module' => $allocation->module, 'session' => $allocation->session]) }}" method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label" for="assessment_name_{{ $allocation->id }}">Assessment name</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="assessment_name_{{ $allocation->id }}" name="name" value="{{ old('name') }}" maxlength="255" required>
                </div>
                @if ($isRegistrar)
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="lecturer_id_{{ $allocation->id }}">Assigned lecturer</label>
                        <select class="form-select @error('lecturer_id') is-invalid @enderror" id="lecturer_id_{{ $allocation->id }}" name="lecturer_id" required>
                            <option value="">Select an assigned lecturer</option>
                            @foreach ($allocation->allocatedLecturers as $lecturer)
                                <option value="{{ $lecturer->id }}" @selected((string) old('lecturer_id') === (string) $lecturer->id)>{{ $lecturer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-12 col-md-2">
                    <label class="form-label" for="assessment_type_{{ $allocation->id }}">Type</label>
                    <select class="form-select @error('assessment_type') is-invalid @enderror" id="assessment_type_{{ $allocation->id }}" name="assessment_type" required>
                        @foreach (['coursework' => 'Coursework', 'test' => 'Test', 'midterm' => 'Midterm', 'final_exam' => 'Final exam'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('assessment_type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="max_score_{{ $allocation->id }}">Maximum score</label>
                    <input class="form-control @error('max_score') is-invalid @enderror" id="max_score_{{ $allocation->id }}" name="max_score" type="number" min="0.01" step="0.01" value="{{ old('max_score', '100') }}" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="weight_{{ $allocation->id }}">Weight (%)</label>
                    <input class="form-control @error('weight_percentage') is-invalid @enderror" id="weight_{{ $allocation->id }}" name="weight_percentage" type="number" min="0.01" max="100" step="0.01" value="{{ old('weight_percentage') }}" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label" for="due_date_{{ $allocation->id }}">Due date</label>
                    <input class="form-control @error('due_date') is-invalid @enderror" id="due_date_{{ $allocation->id }}" name="due_date" type="date" value="{{ old('due_date') }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="starts_at_{{ $allocation->id }}">Opens at</label>
                    <input class="form-control @error('starts_at') is-invalid @enderror" id="starts_at_{{ $allocation->id }}" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="ends_at_{{ $allocation->id }}">Closes at</label>
                    <input class="form-control @error('ends_at') is-invalid @enderror" id="ends_at_{{ $allocation->id }}" name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="question_file_{{ $allocation->id }}">Question document (PDF or DOCX, up to 50 MB)</label>
                    <input class="form-control @error('question_file') is-invalid @enderror" id="question_file_{{ $allocation->id }}" name="question_file" type="file" accept=".pdf,.docx" required>
                </div>
                <div class="col-12"><button class="btn btn-primary" type="submit">Add assessment</button></div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Assessment</th><th>Type</th><th>Maximum</th><th>Weight</th><th>Due</th><th></th></tr></thead>
                <tbody>
                    @forelse ($allocation->assessments as $assessment)
                        <tr>
                            <td><strong>{{ $assessment->name }}</strong></td>
                            <td>{{ str($assessment->assessment_type)->replace('_', ' ')->title() }}</td>
                            <td>{{ $assessment->max_score }}</td>
                            <td>{{ $assessment->weight_percentage }}%</td>
                            <td>
                                @if ($assessment->starts_at && $assessment->ends_at)
                                    {{ $assessment->starts_at->format('M j H:i') }} – {{ $assessment->ends_at->format('M j H:i') }}
                                @else
                                    {{ $assessment->due_date?->format('Y-m-d') ?? 'Unscheduled' }}
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('assessments.submissions.index', $assessment) }}" data-page-link>Submission status</a>
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('assessments.results.index', $assessment) }}" data-page-link>Enter results</a>
                                    <details class="text-start">
                                        <summary>Edit assessment</summary>
                                        <form action="{{ route('assessments.update', $assessment) }}" method="POST" enctype="multipart/form-data" class="row g-2 mt-1">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-12">
                                                <label class="form-label" for="edit_name_{{ $assessment->id }}">Name</label>
                                                <input class="form-control form-control-sm" id="edit_name_{{ $assessment->id }}" name="name" value="{{ $assessment->name }}" maxlength="255" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="edit_type_{{ $assessment->id }}">Type</label>
                                                <select class="form-select form-select-sm" id="edit_type_{{ $assessment->id }}" name="assessment_type" required>
                                                    @foreach (['coursework' => 'Coursework', 'test' => 'Test', 'midterm' => 'Midterm', 'final_exam' => 'Final exam'] as $value => $label)
                                                        <option value="{{ $value }}" @selected($assessment->assessment_type === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label" for="edit_max_score_{{ $assessment->id }}">Maximum score</label>
                                                <input class="form-control form-control-sm" id="edit_max_score_{{ $assessment->id }}" name="max_score" type="number" min="0.01" step="0.01" value="{{ $assessment->max_score }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label" for="edit_weight_{{ $assessment->id }}">Weight (%)</label>
                                                <input class="form-control form-control-sm" id="edit_weight_{{ $assessment->id }}" name="weight_percentage" type="number" min="0.01" max="100" step="0.01" value="{{ $assessment->weight_percentage }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="edit_due_date_{{ $assessment->id }}">Due date</label>
                                                <input class="form-control form-control-sm" id="edit_due_date_{{ $assessment->id }}" name="due_date" type="date" value="{{ $assessment->due_date?->format('Y-m-d') }}">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="edit_starts_at_{{ $assessment->id }}">Opens at</label>
                                                <input class="form-control form-control-sm" id="edit_starts_at_{{ $assessment->id }}" name="starts_at" type="datetime-local" value="{{ $assessment->starts_at?->format('Y-m-d\TH:i') }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="edit_ends_at_{{ $assessment->id }}">Closes at</label>
                                                <input class="form-control form-control-sm" id="edit_ends_at_{{ $assessment->id }}" name="ends_at" type="datetime-local" value="{{ $assessment->ends_at?->format('Y-m-d\TH:i') }}" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="edit_question_file_{{ $assessment->id }}">Replace question document (PDF or DOCX, up to 50 MB)</label>
                                                <input class="form-control form-control-sm" id="edit_question_file_{{ $assessment->id }}" name="question_file" type="file" accept=".pdf,.docx" @required($assessment->question_file_path === null)>
                                                @if ($assessment->question_file_original_name)
                                                    <small class="text-secondary">Current file: {{ $assessment->question_file_original_name }}</small>
                                                @endif
                                            </div>
                                            <div class="col-12"><button class="btn btn-sm btn-outline-primary" type="submit">Save changes</button></div>
                                        </form>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-secondary py-4" colspan="6">No assessments have been set for this module and session.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@empty
    <section class="card"><div class="card-body text-secondary">
        @if ($isRegistrar)
            No teaching allocations have been recorded yet. Assign lecturers to modules and sessions before setting assessments.
        @else
            No module allocations are assigned to your lecturer account yet. Contact the registrar to arrange your teaching allocation.
        @endif
    </div></section>
@endforelse
@endsection
