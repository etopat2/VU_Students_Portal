@extends('layout.layout')

@section('title', 'Students')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">ACADEMIC COMMUNITY</p>
        <h2>Students</h2>
        <p class="page-description">Manage student records and programme information.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('students.create') }}" data-page-link>Add student</a>
</section>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<section class="row g-3 mb-4" aria-label="Student summary">
    <div class="col-12 col-sm-4">
        <article class="card dashboard-stat-card h-100"><div class="card-body">
            <p class="academic-stat-label">Students listed</p><p class="academic-stat-value">{{ $studentCount }}</p><p class="academic-stat-caption">In the student directory</p>
        </div></article>
    </div>
    <div class="col-12 col-sm-4">
        <article class="card dashboard-stat-card h-100"><div class="card-body">
            <p class="academic-stat-label">Active students</p><p class="academic-stat-value">{{ $activeCount }}</p><p class="academic-stat-caption">Currently enrolled</p>
        </div></article>
    </div>
    <div class="col-12 col-sm-4">
        <article class="card dashboard-stat-card h-100"><div class="card-body">
            <p class="academic-stat-label">Programmes</p><p class="academic-stat-value">{{ $programmeCount }}</p><p class="academic-stat-caption">Represented in the directory</p>
        </div></article>
    </div>
</section>

<section class="card academic-table-card">
    <div class="card-body">
        <div class="academic-table-heading">
            <div><h3 class="card-title">Student directory</h3><p class="card-subtitle">Student profiles and enrolment status</p></div>
            <label class="academic-search">
                <span class="visually-hidden">Search students</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg>
                <input class="form-control form-control-sm" type="search" placeholder="Search students" data-table-filter="#studentsTable" autocomplete="off">
            </label>
        </div>
        <div class="table-responsive">
            <table class="table align-middle academic-table" id="studentsTable">
                <thead><tr><th scope="col">Student</th><th scope="col">Student ID</th><th scope="col">Programme</th><th scope="col">Year</th><th scope="col">Status</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr data-filter-row>
                            <td><span class="table-person"><span class="table-avatar" aria-hidden="true">{{ strtoupper(substr($student->name, 0, 1)) }}</span><span><strong>{{ $student->name }}</strong><small>{{ $student->email }}</small></span></span></td>
                            <td>{{ $student->student_number }}</td><td>{{ $student->programme }}</td><td>{{ $student->year }}</td>
                            <td><span class="status-badge {{ $student->status === 'Active' ? 'status-active' : 'status-muted' }}">{{ $student->status }}</span></td>
                            <td><div class="table-actions">
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('students.edit', $student) }}" data-page-link>Edit</a>
                                <form action="{{ route('students.destroy', $student) }}" method="POST" onsubmit="return confirm('Delete this student? Related grades will also be deleted.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="6">No students yet. Add a student to start the directory.</td></tr>
                    @endforelse
                    <tr class="filter-empty d-none"><td class="table-empty" colspan="6">No students match your search.</td></tr>
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </div>
</section>
@endsection
