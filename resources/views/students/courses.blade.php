@extends('layout.layout')

@section('title', 'My courses')

@section('content')
<section class="page-intro">
    <div><p class="eyebrow">LEARNING</p><h2>My courses</h2><p class="page-description">Course information linked to your student record.</p></div>
</section>
<section class="row g-3">
    @forelse ($courses as $course)
        <div class="col-12 col-md-6 col-xl-4">
            <article class="card student-course-card h-100">
                <div class="card-body">
                    <span class="status-badge status-active">{{ $course->status }}</span>
                    <p class="student-course-code">{{ $course->code }}</p>
                    <h3>{{ $course->title }}</h3>
                    <p class="student-course-department">{{ $course->department }}</p>
                    <div class="student-course-meta"><span>{{ $course->credits }} credits</span><span>{{ $course->schedule ?: 'Schedule pending' }}</span></div>
                </div>
            </article>
        </div>
        @empty
        <div class="col-12">
            <div class="empty-state card">
                <div class="card-body">
                    <h3>No courses assigned</h3>
                    <p>Your administrator has not linked any courses to your student record yet.</p>
                </div>
            </div>
        </div>
    @endforelse
</section>
@endsection
