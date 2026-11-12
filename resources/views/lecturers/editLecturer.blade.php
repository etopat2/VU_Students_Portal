@extends('layout.layout')

@section('title', 'Edit lecturer')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">LECTURER DIRECTORY</p>
        <h2>Edit lecturer</h2>
        <p class="page-description">Update {{ $lecturer->name }}’s contact and work details.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('lecturers.index') }}" data-page-link>Back to lecturers</a>
</section>

<section class="card academic-form-card">
    <div class="card-body">
        <form action="{{ route('lecturers.update', $lecturer) }}" method="POST">
            @csrf
            @method('PUT')
            @include('lecturers.lecturerFields', ['lecturer' => $lecturer])
            <div class="form-actions">
                <a class="btn btn-outline-secondary" href="{{ route('lecturers.index') }}" data-page-link>Cancel</a>
                <button class="btn btn-primary" type="submit">Save changes</button>
            </div>
        </form>
    </div>
</section>
@endsection
