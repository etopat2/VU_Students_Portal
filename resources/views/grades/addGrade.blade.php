@extends('layout.layout')

@section('title', 'Add grade')

@section('content')
<section class="page-intro"><div><p class="eyebrow">GRADEBOOK</p><h2>Add grade</h2><p class="page-description">Record a student's assessment result.</p></div><a class="btn btn-outline-secondary" href="{{ route('grades.index') }}" data-page-link>Back to grades</a></section>
@if ($students->isEmpty() || $courses->isEmpty())
    <div class="alert alert-warning">Add at least one student and one course before recording a grade.</div>
@else
    <section class="card academic-form-card"><div class="card-body">
        <form action="{{ route('grades.store') }}" method="POST">
            @csrf
            @include('grades.gradeFields', ['grade' => null])
            <div class="form-actions"><a class="btn btn-outline-secondary" href="{{ route('grades.index') }}" data-page-link>Cancel</a><button class="btn btn-primary" type="submit">Add grade</button></div>
        </form>
    </div></section>
@endif
@endsection
