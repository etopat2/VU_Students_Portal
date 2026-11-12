<div class="topbar">
    <button class="sidebar-toggle btn btn-outline-secondary d-lg-none" type="button" aria-controls="appSidebar" aria-expanded="false" aria-label="Open navigation">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
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