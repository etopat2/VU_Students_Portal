@extends('layout.layout')

@section('title', 'Enter assessment results')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">RESULT ENTRY</p>
        <h2>{{ $assessment->name }}</h2>
        <p class="page-description">{{ $assessment->module->code }} · {{ $assessment->module->title }} · Maximum score: {{ $assessment->max_score }}</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('assessments.index') }}" data-page-link>Back to assessments</a>
</section>

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Student</th><th>Student number</th><th>Score</th><th>Feedback</th><th></th></tr></thead>
            <tbody>
                @forelse ($registrations as $registration)
                    @php($result = $registration->assessmentResults->first())
                    <tr>
                        <td><strong>{{ $registration->semesterRegistration->student->name }}</strong></td>
                        <td>{{ $registration->semesterRegistration->student->student_number }}</td>
                        <td colspan="3">
                            <form action="{{ route('assessment-results.store', $assessment) }}" method="POST" class="row g-2 align-items-end">
                                @csrf
                                <input type="hidden" name="student_module_registration_id" value="{{ $registration->id }}">
                                <div class="col-12 col-sm-3">
                                    <label class="form-label" for="score_{{ $registration->id }}">Score / {{ $assessment->max_score }}</label>
                                    <input class="form-control @error('score') is-invalid @enderror" id="score_{{ $registration->id }}" name="score" type="number" min="0" max="{{ $assessment->max_score }}" step="0.01" value="{{ old('score', $result?->score) }}" required>
                                </div>
                                <div class="col-12 col-sm-7">
                                    <label class="form-label" for="feedback_{{ $registration->id }}">Feedback (optional)</label>
                                    <input class="form-control @error('feedback') is-invalid @enderror" id="feedback_{{ $registration->id }}" name="feedback" maxlength="5000" value="{{ old('feedback', $result?->feedback) }}">
                                </div>
                                <div class="col-12 col-sm-2"><button class="btn btn-primary w-100" type="submit">Save result</button></div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="5">No students are registered for this module in this session.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $registrations->links() }}</div>
</section>
@endsection
