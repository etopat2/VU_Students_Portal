@extends('layout.layout')

@section('title', 'Institution settings')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">SYSTEM ADMINISTRATION</p>
        <h2>Institution settings</h2>
        <p class="page-description">Manage the institution details shown across the portal.</p>
    </div>
</section>

<section class="card academic-form-card">
    <div class="card-body">
        <form action="{{ route('system-settings.update') }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-12 col-md-8">
                <label class="form-label" for="institution_name">Institution name</label>
                <input class="form-control @error('name') is-invalid @enderror" id="institution_name" name="name" value="{{ old('name', $institution->name) }}" maxlength="255" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="institution_code">Institution code</label>
                <input class="form-control @error('code') is-invalid @enderror" id="institution_code" name="code" value="{{ old('code', $institution->code) }}" maxlength="30" required>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="institution_email">Contact email</label>
                <input class="form-control @error('email') is-invalid @enderror" id="institution_email" name="email" type="email" value="{{ old('email', $institution->email) }}" maxlength="255">
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="institution_phone">Contact phone</label>
                <input class="form-control @error('phone') is-invalid @enderror" id="institution_phone" name="phone" value="{{ old('phone', $institution->phone) }}" maxlength="30">
            </div>
            <div class="col-12">
                <label class="form-label" for="institution_address">Address</label>
                <input class="form-control @error('address') is-invalid @enderror" id="institution_address" name="address" value="{{ old('address', $institution->address) }}" maxlength="255">
            </div>
            <div class="col-12"><button class="btn btn-primary" type="submit">Save institution settings</button></div>
        </form>
    </div>
</section>
@endsection
