@extends('layout.layout')

@section('title', 'Dashboard')

@section('content')
<section class="welcome-card card border-0 shadow-sm">
    <div class="welcome-copy">
        <span class="welcome-tag">{{ auth()->user()->isAdmin() ? 'ADMINISTRATOR OVERVIEW' : 'STUDENT OVERVIEW' }}</span>
        <h2>Welcome back, {{ auth()->user()->name }}</h2>
        <p>{{ auth()->user()->isAdmin() ? 'Manage students, courses, and academic performance.' : 'Your academic progress at a glance.' }}</p>
    </div>
    <a class="btn btn-light btn-sm welcome-action" href="{{ route('profile') }}" data-page-link>View profile <span aria-hidden="true">&rarr;</span></a>
</section>

@if (auth()->user()->isAdmin())
    <section class="row g-3 dashboard-stats" aria-label="Administration summary">
        <div class="col-12 col-sm-6 col-xl-3"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Students</span><span class="stat-icon stat-icon-blue" aria-hidden="true">S</span></div>
            <div class="stat-value">{{ $stats['students'] }}</div><p class="stat-caption">{{ $stats['activeStudents'] }} {{ $stats['activeStudents'] === 1 ? 'active student' : 'active students' }}</p>
            <a class="stat-link" href="{{ route('students.index') }}" data-page-link>Manage students <span aria-hidden="true">&rarr;</span></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Courses</span><span class="stat-icon stat-icon-violet" aria-hidden="true">C</span></div>
            <div class="stat-value">{{ $stats['courses'] }}</div><p class="stat-caption">In the course catalogue</p>
            <a class="stat-link" href="{{ route('courses.index') }}" data-page-link>Manage courses <span aria-hidden="true">&rarr;</span></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Grade records</span><span class="stat-icon stat-icon-amber" aria-hidden="true">G</span></div>
            <div class="stat-value">{{ $stats['grades'] }}</div><p class="stat-caption">Assessment results recorded</p>
            <a class="stat-link" href="{{ route('grades.index') }}" data-page-link>Open gradebook <span aria-hidden="true">&rarr;</span></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-3"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Average score</span><span class="stat-icon stat-icon-green" aria-hidden="true">%</span></div>
            <div class="stat-value">{{ number_format($stats['averageScore'], 1) }}<span>%</span></div><p class="stat-caption">Across recorded grades</p>
        </div></article></div>
    </section>
@else
    <section class="row g-3 dashboard-stats" aria-label="Academic summary">
        <div class="col-12 col-sm-6 col-xl-4"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Enrolled courses</span><span class="stat-icon stat-icon-blue" aria-hidden="true">C</span></div>
            <div class="stat-value">{{ $stats['courses'] }}</div><p class="stat-caption">Courses linked to your record</p>
            <a class="stat-link" href="{{ route('student.courses') }}" data-page-link>View courses <span aria-hidden="true">&rarr;</span></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-4"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Grade records</span><span class="stat-icon stat-icon-violet" aria-hidden="true">G</span></div>
            <div class="stat-value">{{ $stats['grades'] }}</div><p class="stat-caption">Assessment results available</p>
            <a class="stat-link" href="{{ route('student.grades') }}" data-page-link>View grades <span aria-hidden="true">&rarr;</span></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-4"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Average score</span><span class="stat-icon stat-icon-green" aria-hidden="true">%</span></div>
            <div class="stat-value">{{ number_format($stats['averageScore'], 1) }}<span>%</span></div><p class="stat-caption">Across your recorded grades</p>
        </div></article></div>
    </section>
@endif

<section class="row g-3 dashboard-charts" aria-label="Academic performance charts">
    <div class="col-12 col-xl-8"><article class="card chart-card h-100"><div class="card-body">
        <div class="chart-heading"><div><h2 class="card-title">Grade distribution</h2><p class="card-subtitle">Live summary of recorded assessment grades</p></div></div>
        @if ($chartLabels->isNotEmpty())
            <div class="chart-canvas-wrap"><canvas id="gradeDistributionChart" data-labels="{{ $chartLabels->toJson() }}" data-values="{{ $chartValues->toJson() }}" role="img" aria-label="Grade distribution chart"></canvas></div>
        @else
            <p class="text-secondary mb-0">Grade distribution will appear after results are recorded.</p>
        @endif
    </div></article></div>
    <div class="col-12 col-xl-4"><article class="card chart-card h-100"><div class="card-body">
        <div class="chart-heading"><div><h2 class="card-title">Recent results</h2><p class="card-subtitle">Latest recorded grades</p></div></div>
        @forelse ($recentGrades as $grade)
            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                <span><strong>{{ $grade->course->code }}</strong><small class="d-block text-secondary">{{ $grade->student->name }}</small></span>
                <span class="grade-badge">{{ $grade->letter_grade }}</span>
            </div>
        @empty
            <p class="text-secondary mb-0">No grade records are available yet.</p>
        @endforelse
    </div></article></div>
</section>

<section class="quick-access-section">
    <div class="section-heading"><div><p class="eyebrow">QUICK ACCESS</p><h2>{{ auth()->user()->isAdmin() ? 'Administration' : 'Your academics' }}</h2></div></div>
    <div class="row g-3">
        @if (auth()->user()->isAdmin())
            <div class="col-12 col-md-6 col-xl-3"><a class="quick-card card h-100" href="{{ route('students.index') }}" data-page-link><span class="quick-icon quick-icon-blue" aria-hidden="true">S</span><span class="quick-card-copy"><span class="quick-title">Student directory</span><span class="quick-description">Review and manage student records.</span></span><span class="quick-arrow" aria-hidden="true">&rarr;</span></a></div>
            <div class="col-12 col-md-6 col-xl-3"><a class="quick-card card h-100" href="{{ route('lecturers.index') }}" data-page-link><span class="quick-icon quick-icon-green" aria-hidden="true">L</span><span class="quick-card-copy"><span class="quick-title">Lecturers</span><span class="quick-description">Keep lecturer and department details current.</span></span><span class="quick-arrow" aria-hidden="true">&rarr;</span></a></div>
            <div class="col-12 col-md-6 col-xl-3"><a class="quick-card card h-100" href="{{ route('courses.index') }}" data-page-link><span class="quick-icon quick-icon-violet" aria-hidden="true">C</span><span class="quick-card-copy"><span class="quick-title">Course catalogue</span><span class="quick-description">Manage courses and course details.</span></span><span class="quick-arrow" aria-hidden="true">&rarr;</span></a></div>
            <div class="col-12 col-md-6 col-xl-3"><a class="quick-card card h-100" href="{{ route('grades.index') }}" data-page-link><span class="quick-icon quick-icon-amber" aria-hidden="true">G</span><span class="quick-card-copy"><span class="quick-title">Gradebook</span><span class="quick-description">Review recorded student results.</span></span><span class="quick-arrow" aria-hidden="true">&rarr;</span></a></div>
        @else
            <div class="col-12 col-md-6"><a class="quick-card card h-100" href="{{ route('student.courses') }}" data-page-link><span class="quick-icon quick-icon-blue" aria-hidden="true">C</span><span class="quick-card-copy"><span class="quick-title">My courses</span><span class="quick-description">View courses assigned to your student record.</span></span><span class="quick-arrow" aria-hidden="true">&rarr;</span></a></div>
            <div class="col-12 col-md-6"><a class="quick-card card h-100" href="{{ route('student.grades') }}" data-page-link><span class="quick-icon quick-icon-violet" aria-hidden="true">G</span><span class="quick-card-copy"><span class="quick-title">My grades</span><span class="quick-description">Review your recorded assessment results.</span></span><span class="quick-arrow" aria-hidden="true">&rarr;</span></a></div>
        @endif
    </div>
</section>
@endsection
