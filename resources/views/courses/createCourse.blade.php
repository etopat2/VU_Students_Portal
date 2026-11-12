@extends('layout.layout')

@section('title', 'Add course')

@section('content')
<section class="page-intro"><div><p class="eyebrow">COURSE CATALOGUE</p><h2>Add course</h2><p class="page-description">Enter the course details and schedule.</p></div><a class="btn btn-outline-secondary" href="{{ route('courses.index') }}" data-page-link>Back to courses</a></section>
<section class="card academic-form-card"><div class="card-body">
    <form action="{{ route('courses.store') }}" method="POST">
        @csrf
        @include('courses.courseFields', ['course' => null])
        <div class="form-actions"><a class="btn btn-outline-secondary" href="{{ route('courses.index') }}" data-page-link>Cancel</a><button class="btn btn-primary" type="submit">Add course</button></div>
    </form>
</div></section>
@endsection
