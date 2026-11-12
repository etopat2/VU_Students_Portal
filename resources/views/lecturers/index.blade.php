@extends('layout.layout')

@section('title', 'Lecturers')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">ACADEMIC COMMUNITY</p>
        <h2>Lecturers</h2>
        <p class="page-description">Keep staff contact and department details up to date.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('lecturers.create') }}" data-page-link>Add lecturer</a>
</section>

@if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

<section class="row g-3 mb-4" aria-label="Lecturer summary">
    <div class="col-12 col-sm-4">
        <article class="card dashboard-stat-card h-100"><div class="card-body">
            <p class="academic-stat-label">Lecturers</p>
            <p class="academic-stat-value">{{ $lecturerCount }}</p>
            <p class="academic-stat-caption">In the staff directory</p>
        </div></article>
    </div>
    <div class="col-12 col-sm-4">
        <article class="card dashboard-stat-card h-100"><div class="card-body">
            <p class="academic-stat-label">Active lecturers</p>
            <p class="academic-stat-value">{{ $activeCount }}</p>
            <p class="academic-stat-caption">Available for teaching</p>
        </div></article>
    </div>
    <div class="col-12 col-sm-4">
        <article class="card dashboard-stat-card h-100"><div class="card-body">
            <p class="academic-stat-label">Departments</p>
            <p class="academic-stat-value">{{ $departmentCount }}</p>
            <p class="academic-stat-caption">Represented in the directory</p>
        </div></article>
    </div>
</section>

<section class="card academic-table-card">
    <div class="card-body">
        <div class="academic-table-heading">
            <div>
                <h3 class="card-title">Lecturer directory</h3>
                <p class="card-subtitle">Staff contact and qualification details</p>
            </div>
            <label class="academic-search">
                <span class="visually-hidden">Search lecturers</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8"/><path d="m16 16 4.5 4.5"/></svg>
                <input class="form-control form-control-sm" type="search" placeholder="Search lecturers" data-table-filter="#lecturersTable" autocomplete="off">
            </label>
        </div>
        <div class="table-responsive">
            <table class="table align-middle academic-table" id="lecturersTable">
                <thead>
                    <tr>
                        <th scope="col">Lecturer</th>
                        <th scope="col">Staff ID</th>
                        <th scope="col">Department</th>
                        <th scope="col">Qualification</th>
                        <th scope="col">Phone</th>
                        <th scope="col">Status</th>
                        <th scope="col"><span class="visually-hidden">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lecturers as $lecturer)
                        <tr data-filter-row>
                            <td>
                                <span class="table-person">
                                    <span class="table-avatar" aria-hidden="true">{{ strtoupper(substr($lecturer->name, 0, 1)) }}</span>
                                    <span><strong>{{ $lecturer->name }}</strong><small>{{ $lecturer->email }}</small></span>
                                </span>
                            </td>
                            <td>{{ $lecturer->lecturer_number }}</td>
                            <td>{{ $lecturer->department }}</td>
                            <td>{{ $lecturer->qualification }}</td>
                            <td>{{ $lecturer->phone ?: '—' }}</td>
                            <td><span class="status-badge {{ $lecturer->status === 'Active' ? 'status-active' : 'status-muted' }}">{{ $lecturer->status }}</span></td>
                            <td>
                                <div class="table-actions">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('lecturers.edit', $lecturer) }}" data-page-link>Edit</a>
                                    <form action="{{ route('lecturers.destroy', $lecturer) }}" method="POST" onsubmit="return confirm('Remove this lecturer from the directory?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="table-empty" colspan="7">No lecturers have been added yet.</td></tr>
                    @endforelse
                    <tr class="filter-empty d-none"><td class="table-empty" colspan="7">No lecturers match your search.</td></tr>
                </tbody>
            </table>
        </div>
        {{ $lecturers->links() }}
    </div>
</section>
@endsection
