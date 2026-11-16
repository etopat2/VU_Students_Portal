<div class="topbar">
    <button class="sidebar-toggle btn btn-outline-secondary d-lg-none" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation">
        <i class="fa fa-bars" aria-hidden="true"></i>
    </button>
    <div class="topbar-heading">
        <p class="eyebrow">VU STUDENT PORTAL</p>
        <h1 id="pageTitle">@yield('title')</h1>
    </div>
    <div class="user-info">
        <span class="user-name">{{ auth()->user()->name }}</span>
        <span class="user-avatar" aria-label="{{ auth()->user()->name }}'s profile">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
    </div>
</div>