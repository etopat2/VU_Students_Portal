@extends('layout.layout')

@section('title', 'Add lecturer')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">LECTURER DIRECTORY</p>
        <h2>Add lecturer</h2>
        <p class="page-description">Enter the lecturer’s contact and work details.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('lecturers.index') }}" data-page-link>Back to lecturers</a>
</section>

<section class="card academic-form-card">
    <div class="card-body">
        <form action="{{ route('lecturers.store') }}" method="POST">
            @csrf
            @include('lecturers.lecturerFields', ['lecturer' => null])
            <div class="form-actions">
                <a class="btn btn-outline-secondary" href="{{ route('lecturers.index') }}" data-page-link>Cancel</a>
                <button class="btn btn-primary" type="submit">Add lecturer</button>
            </div>
        </form>
    </div>
</section>
@endsection

