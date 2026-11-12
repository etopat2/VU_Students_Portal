@extends('layout.layout')

@section('title', 'Profile')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">YOUR ACCOUNT</p>
        <h2>{{ $user->isAdmin() ? 'Administrator profile' : 'Student profile' }}</h2>
        <p class="page-description">Your account details and contact information.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('settings') }}" data-page-link>Edit account</a>
</section>

<div class="row g-3">
    <div class="col-12 col-xl-4">
        <article class="card profile-summary h-100">
            <div class="card-body">
                <div class="profile-avatar" aria-hidden="true">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <h3>{{ $user->name }}</h3>
                <p class="profile-role">{{ ucfirst($user->role) }}</p>
                <span class="badge rounded-pill text-bg-success-subtle text-success-emphasis">Account active</span>
                @if ($user->student)
                    <div class="profile-summary-divider"></div>
                    <p class="profile-id-label">Student ID</p>
                    <p class="profile-id">{{ $user->student->student_number }}</p>
                @endif
            </div>
        </article>
    </div>
    <div class="col-12 col-xl-8">
        <article class="card detail-card h-100">
            <div class="card-body">
                <div class="detail-heading">
                    <div>
                        <h3 class="card-title">Personal information</h3>
                        <p class="card-subtitle">Your details on file</p>
                    </div>
                    <a class="btn btn-outline-primary btn-sm" href="{{ route('settings') }}" data-page-link>Account settings</a>
                </div>
                <dl class="profile-details">
                    <div><dt>Full name</dt><dd>{{ $user->name }}</dd></div>
                    <div><dt>Email address</dt><dd>{{ $user->email }}</dd></div>
                    <div><dt>Account role</dt><dd>{{ ucfirst($user->role) }}</dd></div>
                    @if ($user->student)
                        <div><dt>Student number</dt><dd>{{ $user->student->student_number }}</dd></div>
                        <div><dt>Programme</dt><dd>{{ $user->student->programme }}</dd></div>
                        <div><dt>Year</dt><dd>{{ $user->student->year }}</dd></div>
                        <div><dt>Academic status</dt><dd>{{ $user->student->status }}</dd></div>
                    @endif
                </dl>
            </div>
        </article>
    </div>
</div>
@endsection
