@extends('layout.layout')

@section('title', 'Grades')

@section('content')
<section class="page-intro">
    <div><p class="eyebrow">ACADEMIC PERFORMANCE</p><h2>Grades</h2><p class="page-description">Record and review student assessment results.</p></div>
    <a class="btn btn-primary" href="{{ route('grades.create') }}" data-page-link>Add grade</a>
</section>

@if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<section class="row g-3 mb-4" aria-label="Grade summary">
    <div class="col-12 col-sm-4"><article class="card dashboard-stat-card h-100"><div class="card-body"><p class="academic-stat-label">Results posted</p><p class="academic-stat-value">{{ $gradeCount }}</p><p class="academic-stat-caption">In the gradebook</p></div></article></div>
    <div class="col-12 col-sm-4"><article class="card dashboard-stat-card h-100"><div class="card-body"><p class="academic-stat-label">Average score</p><p class="academic-stat-value">{{ number_format($averageScore, 1) }}<span class="academic-stat-unit">%</span></p><p class="academic-stat-caption">Across recorded results</p></div></article></div>
    <div class="col-12 col-sm-4"><article class="card dashboard-stat-card h-100"><div class="card-body"><p class="academic-stat-label">Highest score</p><p class="academic-stat-value">{{ number_format((float) $highestScore, 1) }}<span class="academic-stat-unit">%</span></p><p class="academic-stat-caption">Best recorded result</p></div></article></div>
</section>

<section class="card academic-table-card">
    <div class="card-body">
        <div class="academic-table-heading">
            <div><h3 class="card-title">Assessment results</h3><p class="card-subtitle">Student performance by course and assessment</p></div>
            <label class="academic-search"><span class="visually-hidden">Search grades</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg><input class="form-control form-control-sm" type="search" placeholder="Search grades" data-table-filter="#gradesTable" autocomplete="off"></label>
        </div>
        <div class="table-responsive">
            <table class="table align-middle academic-table" id="gradesTable">
                <thead><tr><th scope="col">Student</th><th scope="col">Course</th><th scope="col">Assessment</th><th scope="col">Term</th><th scope="col">Score</th><th scope="col">Grade</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($grades as $grade)
                        <tr data-filter-row>
                            <td><span class="table-course"><strong>{{ $grade->student->name }}</strong><small>{{ $grade->student->student_number }}</small></span></td>
                            <td><span class="table-course"><strong>{{ $grade->course->code }}</strong><small>{{ $grade->course->title }}</small></span></td>
                            <td>{{ $grade->assessment }}</td><td>{{ $grade->term }}</td><td><span class="grade-score">{{ number_format((float) $grade->score, 1) }}%</span></td>
                            <td><span class="grade-badge grade-{{ strtolower(str_replace('+', '-plus', $grade->letter_grade)) }}">{{ $grade->letter_grade }}</span></td>
                            <td><div class="table-actions"><a class="btn btn-sm btn-outline-primary" href="{{ route('grades.edit', $grade) }}" data-page-link>Edit</a><form action="{{ route('grades.destroy', $grade) }}" method="POST" onsubmit="return confirm('Delete this grade?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form></div></td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="7">No grades yet. Add a result to start the gradebook.</td></tr>
                    @endforelse
                    <tr class="filter-empty d-none"><td class="table-empty" colspan="7">No grades match your search.</td></tr>
                </tbody>
            </table>
        </div>
        {{ $grades->links() }}
    </div>
</section>
@endsection
