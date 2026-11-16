@extends('layout.layout')

@section('title', $studySession ? 'Edit study session' : 'Add study session')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">ACADEMIC SETUP</p>
        <h2>{{ $studySession ? 'Edit study session' : 'Add study session' }}</h2>
        <p class="page-description">Set the session name, audience, and whether students can request it.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('study-sessions.index') }}" data-page-link>Back to sessions</a>
</section>

<section class="card academic-form-card">
    <div class="card-body">
        <form action="{{ $studySession ? route('study-sessions.update', $studySession) : route('study-sessions.store') }}" method="POST">
            @csrf
            @if ($studySession) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label" for="name">Session name</label>
                    <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $studySession?->name) }}" maxlength="80" required placeholder="e.g. Weekend">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="code">Session code</label>
                    <input class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $studySession?->code) }}" maxlength="20" required placeholder="e.g. WEEKEND">
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="audience">Audience</label>
                    <select class="form-select @error('audience') is-invalid @enderror" id="audience" name="audience" required>
                        <option value="all_students" @selected(old('audience', $studySession?->audience ?? 'all_students') === 'all_students')>All students</option>
                        <option value="in_service_teachers" @selected(old('audience', $studySession?->audience) === 'in_service_teachers')>In-service teachers</option>
                    </select>
                    @error('audience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="description">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" maxlength="255">{{ old('description', $studySession?->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <input type="hidden" name="is_active" value="0">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked((bool) old('is_active', $studySession?->is_active ?? true))>
                        <span class="form-check-label">Available for student requests and new assignments</span>
                    </label>
                </div>
            </div>
            @error('study_session')<div class="text-danger small mt-3">{{ $message }}</div>@enderror
            <div class="form-actions">
                <a class="btn btn-outline-secondary" href="{{ route('study-sessions.index') }}" data-page-link>Cancel</a>
                <button class="btn btn-primary" type="submit">{{ $studySession ? 'Save changes' : 'Create session' }}</button>
            </div>
        </form>
    </div>
</section>
@endsection
