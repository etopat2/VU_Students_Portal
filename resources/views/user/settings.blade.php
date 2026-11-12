@extends('layout.layout')

@section('title', 'Settings')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">ACCOUNT PREFERENCES</p>
        <h2>Portal settings</h2>
        <p class="page-description">Update your sign-in details and personal account information.</p>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('profile') }}" data-page-link>View profile</a>
</section>

@if (session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
@endif

<div class="row g-3">
    <div class="col-12 col-xl-8">
        <article class="card detail-card">
            <div class="card-body">
                <div class="detail-heading">
                    <div>
                        <h3 class="card-title">Profile and account details</h3>
                        <p class="card-subtitle">Your role and academic records can only be changed by an administrator.</p>
                    </div>
                </div>

                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label" for="name">Full name</label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" autocomplete="name" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="email">Email address</label>
                        <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" autocomplete="email" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <hr class="my-4">
                    <h4 class="h6 mb-1">Change password</h4>
                    <p class="text-secondary small mb-3">Leave these fields blank to keep your current password.</p>

                    <div class="mb-3">
                        <label class="form-label" for="current_password">Current password</label>
                        <input class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" type="password" autocomplete="current-password">
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password">New password</label>
                            <input class="form-control @error('password') is-invalid @enderror" id="password" name="password" type="password" autocomplete="new-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="password_confirmation">Confirm new password</label>
                            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit">Save changes</button>
                        <a class="btn btn-outline-secondary" href="{{ route('profile') }}" data-page-link>Cancel</a>
                    </div>
                </form>
            </div>
        </article>
    </div>

    <div class="col-12 col-xl-4">
        <article class="card settings-card h-100">
            <div class="card-body">
                <span class="settings-icon settings-icon-amber" aria-hidden="true">S</span>
                <h3>Security and access</h3>
                <p>Sign out when using a shared device. Your role and permissions are managed by the portal administrator.</p>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm" type="submit">Log out securely</button>
                </form>
            </div>
        </article>
    </div>
</div>
@endsection
