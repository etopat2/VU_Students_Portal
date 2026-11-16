@extends('layout.layout')

@section('title', 'Dashboard')

@section('content')
<section class="welcome-card card border-0 shadow-sm">
    <div class="welcome-copy">
        <span class="welcome-tag">{{ auth()->user()->isAdmin() ? 'SYSTEM ADMINISTRATOR OVERVIEW' : 'STUDENT OVERVIEW' }}</span>
        <h2>Welcome back, {{ auth()->user()->name }}</h2>
        <p>{{ auth()->user()->isAdmin() ? 'Manage institution setup, user access, and system activity.' : 'Your academic progress at a glance.' }}</p>
    </div>
    <div class="welcome-actions">
        <span class="dashboard-live-status" id="dashboardUpdatedAt" aria-live="polite">Refreshing every 30 seconds</span>
        <a class="btn btn-light btn-sm welcome-action" href="{{ route('profile') }}" data-page-link>View profile <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
    </div>
</section>

@if (auth()->user()->isAdmin())
    <section class="row g-3 dashboard-stats" id="dashboardStats" data-dashboard-metrics-url="{{ $metricsUrl }}" aria-label="System administration summary">
        <div class="col-12 col-sm-6 col-xl-2"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>User accounts</span><span class="stat-icon stat-icon-blue" aria-hidden="true"><i class="fa fa-users"></i></span></div>
            <div class="stat-value" data-dashboard-stat="users">{{ $stats['users'] }}</div><p class="stat-caption">Across all access roles</p>
            <a class="stat-link" href="{{ route('users.index') }}" data-page-link>Manage users <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-2"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Students</span><span class="stat-icon stat-icon-green" aria-hidden="true"><i class="fa fa-graduation-cap"></i></span></div>
            <div class="stat-value" data-dashboard-stat="students">{{ $stats['students'] }}</div><p class="stat-caption">Student records in the portal</p>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-2"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Courses</span><span class="stat-icon stat-icon-blue" aria-hidden="true"><i class="fa fa-book"></i></span></div>
            <div class="stat-value" data-dashboard-stat="courses">{{ $stats['courses'] }}</div><p class="stat-caption">Courses in the catalogue</p>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-2"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Faculties</span><span class="stat-icon stat-icon-amber" aria-hidden="true"><i class="fa fa-university"></i></span></div>
            <div class="stat-value" data-dashboard-stat="faculties">{{ $stats['faculties'] }}</div><p class="stat-caption">Institutional units configured</p>
            <a class="stat-link" href="{{ route('academic-structure.index', 'faculties') }}" data-page-link>Manage faculties <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-2"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Audit entries</span><span class="stat-icon stat-icon-violet" aria-hidden="true"><i class="fa fa-history"></i></span></div>
            <div class="stat-value" data-dashboard-stat="auditLogs">{{ $stats['auditLogs'] }}</div><p class="stat-caption">System activity records</p>
            <a class="stat-link" href="{{ route('audit-logs.index') }}" data-page-link>Review audit logs <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-2"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Lecturers</span><span class="stat-icon stat-icon-green" aria-hidden="true"><i class="fa fa-user"></i></span></div>
            <div class="stat-value" data-dashboard-stat="lecturers">{{ $stats['lecturers'] }}</div><p class="stat-caption">Lecturer records in the portal</p>
        </div></article></div>
    </section>
@else
    <section class="row g-3 dashboard-stats" id="dashboardStats" data-dashboard-metrics-url="{{ $metricsUrl }}" aria-label="Academic summary">
        <div class="col-12 col-sm-6 col-xl-4"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Enrolled courses</span><span class="stat-icon stat-icon-blue" aria-hidden="true"><i class="fa fa-book"></i></span></div>
            <div class="stat-value" data-dashboard-stat="courses">{{ $stats['courses'] }}</div><p class="stat-caption">Courses linked to your record</p>
            <a class="stat-link" href="{{ route('student.courses') }}" data-page-link>View courses <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-4"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Grade records</span><span class="stat-icon stat-icon-violet" aria-hidden="true"><i class="fa fa-file-text-o"></i></span></div>
            <div class="stat-value" data-dashboard-stat="grades">{{ $stats['grades'] }}</div><p class="stat-caption">Assessment results available</p>
            <a class="stat-link" href="{{ route('student.grades') }}" data-page-link>View grades <i class="fa fa-arrow-right" aria-hidden="true"></i></a>
        </div></article></div>
        <div class="col-12 col-sm-6 col-xl-4"><article class="card dashboard-stat-card h-100"><div class="card-body">
            <div class="stat-heading"><span>Average score</span><span class="stat-icon stat-icon-green" aria-hidden="true"><i class="fa fa-percent"></i></span></div>
            <div class="stat-value"><span data-dashboard-stat="averageScore">{{ number_format($stats['averageScore'], 1) }}</span><span>%</span></div><p class="stat-caption">Across your recorded grades</p>
        </div></article></div>
    </section>
@endif

@if (auth()->user()->isAdmin())
    <section class="row g-3 dashboard-charts mb-3" aria-label="Portal overview charts">
        <div class="col-12 col-xl-6"><article class="card chart-card h-100"><div class="card-body">
            <div class="chart-heading"><div><h2 class="card-title">User accounts by role</h2><p class="card-subtitle">Current account distribution from the user records</p></div></div>
            <div class="chart-canvas-wrap"><canvas id="userRolesChart" data-labels="{{ $dashboardCharts['userRoles']->pluck('label')->toJson() }}" data-values="{{ $dashboardCharts['userRoles']->pluck('total')->toJson() }}" role="img" aria-label="User accounts grouped by role"></canvas></div>
        </div></article></div>
        <div class="col-12 col-xl-6"><article class="card chart-card h-100"><div class="card-body">
            <div class="chart-heading"><div><h2 class="card-title">Students by programme</h2><p class="card-subtitle">Student records grouped by programme</p></div></div>
            <div class="chart-canvas-wrap"><canvas id="studentsByProgrammeChart" data-labels="{{ $dashboardCharts['studentsByProgramme']->pluck('label')->toJson() }}" data-values="{{ $dashboardCharts['studentsByProgramme']->pluck('total')->toJson() }}" role="img" aria-label="Student records grouped by programme"></canvas></div>
        </div></article></div>
    </section>
    <section class="card chart-card mb-4">
        <div class="card-body">
            <div class="chart-heading"><div><h2 class="card-title">Recent system activity</h2><p class="card-subtitle">Latest logged administration and security changes</p></div></div>
            <div id="recentAuditLogs">
            @forelse ($recentAuditLogs as $log)
                <div class="d-flex flex-wrap justify-content-between gap-2 border-bottom py-2">
                    <span><strong>{{ $log->action }}</strong><small class="d-block text-secondary">{{ $log->user?->name ?? 'System' }}</small></span>
                    <time class="text-secondary" datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('M j, Y H:i') }}</time>
                </div>
            @empty
                <p class="text-secondary mb-0">System activity will appear here after sensitive actions are recorded.</p>
            @endforelse
            </div>
            <a class="btn btn-sm btn-outline-primary mt-3" href="{{ route('audit-logs.index') }}" data-page-link>Open audit logs</a>
        </div>
    </section>
@else
    <section class="row g-3 dashboard-charts" aria-label="Academic performance charts">
        <div class="col-12 col-xl-8"><article class="card chart-card h-100"><div class="card-body">
            <div class="chart-heading"><div><h2 class="card-title">Grade distribution</h2><p class="card-subtitle">Live summary of your recorded assessment grades</p></div></div>
            <div class="chart-canvas-wrap"><canvas id="gradeDistributionChart" data-labels="{{ $chartLabels->toJson() }}" data-values="{{ $chartValues->toJson() }}" role="img" aria-label="Grade distribution chart"></canvas></div>
        </div></article></div>
        <div class="col-12 col-xl-4"><article class="card chart-card h-100"><div class="card-body">
            <div class="chart-heading"><div><h2 class="card-title">Recent results</h2><p class="card-subtitle">Your latest recorded grades</p></div></div>
            <div id="recentGrades">
            @forelse ($recentGrades as $grade)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <span><strong>{{ $grade->course->code }}</strong><small class="d-block text-secondary">{{ $grade->course->title }}</small></span>
                    <span class="grade-badge">{{ $grade->letter_grade }}</span>
                </div>
            @empty
                <p class="text-secondary mb-0">No grade records are available yet.</p>
            @endforelse
            </div>
        </div></article></div>
        <div class="col-12"><article class="card chart-card"><div class="card-body">
            <div class="chart-heading"><div><h2 class="card-title">Average score by course</h2><p class="card-subtitle">Your average recorded result for each course</p></div></div>
            <div class="chart-canvas-wrap"><canvas id="courseAveragesChart" data-labels="{{ $dashboardCharts['courseAverages']->pluck('label')->toJson() }}" data-values="{{ $dashboardCharts['courseAverages']->pluck('total')->toJson() }}" role="img" aria-label="Average score by course"></canvas></div>
        </div></article></div>
    </section>
@endif

<section class="quick-access-section">
    <div class="section-heading"><div><p class="eyebrow">QUICK ACCESS</p><h2>{{ auth()->user()->isAdmin() ? 'Administration' : 'Your academics' }}</h2></div></div>
    <div class="row g-3">
        @if (auth()->user()->isAdmin())
            <div class="col-12 col-md-6 col-xl-3"><a class="quick-card card h-100" href="{{ route('system-settings.edit') }}" data-page-link><span class="quick-icon quick-icon-blue" aria-hidden="true"><i class="fa fa-building-o"></i></span><span class="quick-card-copy"><span class="quick-title">Institution settings</span><span class="quick-description">Update the institution's contact and identity details.</span></span><i class="fa fa-arrow-right quick-arrow" aria-hidden="true"></i></a></div>
            <div class="col-12 col-md-6 col-xl-3"><a class="quick-card card h-100" href="{{ route('users.index') }}" data-page-link><span class="quick-icon quick-icon-green" aria-hidden="true"><i class="fa fa-user-plus"></i></span><span class="quick-card-copy"><span class="quick-title">Users &amp; roles</span><span class="quick-description">Create accounts and manage access assignments.</span></span><i class="fa fa-arrow-right quick-arrow" aria-hidden="true"></i></a></div>
            <div class="col-12 col-md-6 col-xl-3"><a class="quick-card card h-100" href="{{ route('academic-structure.index', 'faculties') }}" data-page-link><span class="quick-icon quick-icon-violet" aria-hidden="true"><i class="fa fa-sitemap"></i></span><span class="quick-card-copy"><span class="quick-title">Institution setup</span><span class="quick-description">Manage faculties, departments, and academic structure.</span></span><i class="fa fa-arrow-right quick-arrow" aria-hidden="true"></i></a></div>
            <div class="col-12 col-md-6 col-xl-3"><a class="quick-card card h-100" href="{{ route('audit-logs.index') }}" data-page-link><span class="quick-icon quick-icon-amber" aria-hidden="true"><i class="fa fa-history"></i></span><span class="quick-card-copy"><span class="quick-title">Audit logs</span><span class="quick-description">Review the history of sensitive system changes.</span></span><i class="fa fa-arrow-right quick-arrow" aria-hidden="true"></i></a></div>
        @else
            <div class="col-12"><a class="quick-card card h-100" href="{{ route('student.study-session') }}" data-page-link><span class="quick-icon quick-icon-blue" aria-hidden="true"><i class="fa fa-clock-o"></i></span><span class="quick-card-copy"><span class="quick-title">Study session &amp; program</span><span class="quick-description">{{ $student?->studySession?->name ?? 'No session assigned' }} · {{ $student?->studyProgram?->name ?? 'No program assigned' }}. View your assignment or request a change.</span></span><i class="fa fa-arrow-right quick-arrow" aria-hidden="true"></i></a></div>
            <div class="col-12 col-md-6"><a class="quick-card card h-100" href="{{ route('student.courses') }}" data-page-link><span class="quick-icon quick-icon-blue" aria-hidden="true"><i class="fa fa-bookmark"></i></span><span class="quick-card-copy"><span class="quick-title">My courses</span><span class="quick-description">View courses assigned to your student record.</span></span><i class="fa fa-arrow-right quick-arrow" aria-hidden="true"></i></a></div>
            <div class="col-12 col-md-6"><a class="quick-card card h-100" href="{{ route('student.grades') }}" data-page-link><span class="quick-icon quick-icon-violet" aria-hidden="true"><i class="fa fa-line-chart"></i></span><span class="quick-card-copy"><span class="quick-title">My grades</span><span class="quick-description">Review your recorded assessment results.</span></span><i class="fa fa-arrow-right quick-arrow" aria-hidden="true"></i></a></div>
        @endif
    </div>
</section>
@endsection
