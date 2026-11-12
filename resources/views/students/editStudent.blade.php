@extends('layout.layout')

@section('title', 'Edit student')

@section('content')
<section class="page-intro">
    <div><p class="eyebrow">STUDENT DIRECTORY</p><h2>Edit student</h2><p class="page-description">Update {{ $student->name }}'s academic and contact details.</p></div>
    <a class="btn btn-outline-secondary" href="{{ route('students.index') }}" data-page-link>Back to students</a>
</section>

<section class="card academic-form-card">
    <div class="card-body">
        <form action="{{ route('students.update', $student) }}" method="POST">
            @csrf
            @method('PUT')
            @include('students.studentFields', ['student' => $student])
            <div class="form-actions"><a class="btn btn-outline-secondary" href="{{ route('students.index') }}" data-page-link>Cancel</a><button class="btn btn-primary" type="submit">Save changes</button></div>
        </form>
    </div>
</section>
@endsection
