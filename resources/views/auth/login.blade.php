<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | VU Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/vu-favicon.png') }}" sizes="64x64">
    {!! \Devrabiul\ToastMagic\Facades\ToastMagic::styles() !!}
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body class="auth-page">
    <main class="auth-card card border-0 shadow">
        <div class="card-body">
            <a class="auth-brand" href="{{ route('login') }}">
                <img class="brand-mark" src="{{ asset('images/vu-logo.png') }}" alt="">
                <span><strong>VU Student</strong><small>PORTAL</small></span>
            </a>
            <p class="eyebrow mt-4 mb-2">SECURE ACCESS</p>
            <h1 class="h3 mb-2">Welcome back</h1>
            <p class="text-secondary mb-4">Sign in to your administrator or student account.</p>

            <form method="POST" action="{{ route('login.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="email">Email address</label>
                    <input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" id="remember" name="remember" type="checkbox" value="1">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button class="btn btn-primary w-100" type="submit">Sign in</button>
            </form>
        </div>
    </main>
    @include('partials.toast-notifications')
    {!! \Devrabiul\ToastMagic\Facades\ToastMagic::scripts() !!}
</body>
</html>

