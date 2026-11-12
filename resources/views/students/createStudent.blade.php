@extends('layout.layout')

@section('title', 'Add student')

@section('content')
<section class="page-intro">
    <div><p class="eyebrow">STUDENT DIRECTORY</p><h2>Add student</h2><p class="page-description">Enter the student's academic and contact details.</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('students.index') }}" data-page-link>Back to students</a>
</section>

<section class="card academic-form-card">
    <div class="card-body">
        <form action="{{ route('students.store') }}" method="POST">
            @csrf
            @include('students.studentFields', ['student' => null])
            <div class="form-actions"><a class="btn btn-outline-secondary" href="{{ route('students.index') }}" data-page-link>Cancel</a><button class="btn btn-primary" type="submit">Add student</button></div>
        </form>
    </div>
</section>
@endsection
