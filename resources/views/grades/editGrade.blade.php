@extends('layout.layout')

@section('title', 'Edit grade')

@section('content')
<section class="page-intro"><div><p class="eyebrow">GRADEBOOK</p><h2>Edit grade</h2><p class="page-description">Update the selected assessment result.</p></div><a class="btn btn-outline-secondary" href="{{ route('grades.index') }}" data-page-link>Back to grades</a></section>
<section class="card academic-form-card"><div class="card-body">
    <form action="{{ route('grades.update', $grade) }}" method="POST">
        @csrf
        @method('PUT')
        @include('grades.gradeFields', ['grade' => $grade])
        <div class="form-actions"><a class="btn btn-outline-secondary" href="{{ route('grades.index') }}" data-page-link>Cancel</a><button class="btn btn-primary" type="submit">Save changes</button></div>
    </form>
</div></section>
@endsection
