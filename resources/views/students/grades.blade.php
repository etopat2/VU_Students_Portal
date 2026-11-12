@extends('layout.layout')

@section('title', 'My grades')

@section('content')
<section class="page-intro">
    <div><p class="eyebrow">ACADEMIC PERFORMANCE</p><h2>My grades</h2><p class="page-description">Assessment results recorded for your student account.</p></div>
</section>
<section class="card academic-table-card">
    <div class="card-body">
        <div class="academic-table-heading"><div><h3 class="card-title">Assessment results</h3><p class="card-subtitle">Results recorded for your student account</p></div></div>
        <div class="table-responsive">
            <table class="table align-middle academic-table">
                <thead><tr><th scope="col">Course</th><th scope="col">Assessment</th><th scope="col">Term</th><th scope="col">Score</th><th scope="col">Grade</th></tr></thead>
                <tbody>
                    @forelse ($grades as $grade)
                        <tr><td><span class="table-course"><strong>{{ $grade->course->code }}</strong><small>{{ $grade->course->title }}</small></span></td><td>{{ $grade->assessment }}</td><td>{{ $grade->term }}</td><td>{{ number_format((float) $grade->score, 1) }}%</td><td><span class="grade-badge">{{ $grade->letter_grade }}</span></td></tr>
                    @empty
                        <tr><td class="table-empty" colspan="5">No grades have been recorded for your account yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
