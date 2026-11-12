@extends('layout.layout')

@section('title', 'Courses')

@section('content')
<section class="page-intro">
    <div><p class="eyebrow">LEARNING</p><h2>Courses</h2><p class="page-description">Manage course catalogue, teaching teams, and schedules.</p></div>
    <a class="btn btn-primary" href="{{ route('courses.create') }}" data-page-link>Add course</a>
</section>

@if (session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

<section class="row g-3 mb-4" aria-label="Course summary">
    <div class="col-12 col-sm-4"><article class="card dashboard-stat-card h-100"><div class="card-body"><p class="academic-stat-label">Courses</p><p class="academic-stat-value">{{ $courseCount }}</p><p class="academic-stat-caption">In the course catalogue</p></div></article></div>
    <div class="col-12 col-sm-4"><article class="card dashboard-stat-card h-100"><div class="card-body"><p class="academic-stat-label">Credit units</p><p class="academic-stat-value">{{ $creditCount }}</p><p class="academic-stat-caption">Across listed courses</p></div></article></div>
    <div class="col-12 col-sm-4"><article class="card dashboard-stat-card h-100"><div class="card-body"><p class="academic-stat-label">Departments</p><p class="academic-stat-value">{{ $departmentCount }}</p><p class="academic-stat-caption">Represented in the catalogue</p></div></article></div>
</section>

<section class="card academic-table-card">
    <div class="card-body">
        <div class="academic-table-heading">
            <div><h3 class="card-title">Course catalogue</h3><p class="card-subtitle">Course details and schedules</p></div>
            <label class="academic-search"><span class="visually-hidden">Search courses</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg><input class="form-control form-control-sm" type="search" placeholder="Search courses" data-table-filter="#coursesTable" autocomplete="off"></label>
        </div>
        <div class="table-responsive">
            <table class="table align-middle academic-table" id="coursesTable">
                <thead><tr><th scope="col">Course</th><th scope="col">Department</th><th scope="col">Instructor</th><th scope="col">Credits</th><th scope="col">Schedule</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr data-filter-row>
                            <td><span class="table-course"><strong>{{ $course->code }}</strong><small>{{ $course->title }}</small></span></td><td>{{ $course->department }}</td><td>{{ $course->instructor }}</td><td>{{ $course->credits }}</td><td>{{ $course->schedule ?: 'Not scheduled' }}</td>
                            <td><span class="status-badge {{ $course->status === 'In progress' ? 'status-active' : 'status-muted' }}">{{ $course->status }}</span></td>
                            <td><div class="table-actions"><a class="btn btn-sm btn-outline-primary" href="{{ route('courses.edit', $course) }}" data-page-link>Edit</a><form action="{{ route('courses.destroy', $course) }}" method="POST" onsubmit="return confirm('Delete this course? Related grades will also be deleted.')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form></div></td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="7">No courses yet. Add a course to start the catalogue.</td></tr>
                    @endforelse
                    <tr class="filter-empty d-none"><td class="table-empty" colspan="7">No courses match your search.</td></tr>
                </tbody>
            </table>
        </div>
        {{ $courses->links() }}
    </div>
</section>
@endsection
