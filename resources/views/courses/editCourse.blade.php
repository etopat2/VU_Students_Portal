@extends('layout.layout')

@section('title', 'Edit course')

@section('content')
<section class="page-intro"><div><p class="eyebrow">COURSE CATALOGUE</p><h2>Edit course</h2><p class="page-description">Update {{ $course->code }} — {{ $course->title }}.</p></div><a class="btn btn-outline-secondary" href="{{ route('courses.index') }}" data-page-link>Back to courses</a></section>
<section class="card academic-form-card"><div class="card-body">
    <form action="{{ route('courses.update', $course) }}" method="POST">
        @csrf
        @method('PUT')
        @include('courses.courseFields', ['course' => $course])
        <div class="form-actions"><a class="btn btn-outline-secondary" href="{{ route('courses.index') }}" data-page-link>Cancel</a><button class="btn btn-primary" type="submit">Save changes</button></div>
    </form>
</div></section>
@endsection
