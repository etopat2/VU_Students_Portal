<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | VU Student Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/vu-favicon.png') }}" sizes="64x64">
    {!! \Devrabiul\ToastMagic\Facades\ToastMagic::styles() !!}
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <div class="app-shell">
        @include('partials.navbar')
        <button class="sidebar-backdrop" id="sidebarBackdrop" type="button" aria-label="Close navigation" tabindex="-1"></button>

        <div class="app-main">
            @include('partials.topbar')

            <main class="page-content" id="pageContent" tabindex="-1">
                @yield('content')
            </main>
        </div>
    </div>
    @include('partials.footer')

    @include('partials.toast-notifications')
    {!! \Devrabiul\ToastMagic\Facades\ToastMagic::scripts() !!}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/dashboard.js') }}"></script>
</body>
</html>