<aside class="sidebar" id="appSidebar" tabindex="-1" aria-label="Main navigation">
    <a class="sidebar-brand" href="{{ route('dashboard') }}">
        <span class="brand-mark" aria-hidden="true">V</span>
        <span>
            <span class="brand-name">VU Student</span>
            <span class="brand-caption">PORTAL</span>
        </span>
    </a>
    <button class="btn-close btn-close-white sidebar-close d-lg-none" type="button" aria-label="Close navigation"></button>

    <p class="sidebar-label">{{ auth()->user()->isAdmin() ? 'Administration' : 'Workspace' }}</p>
    <nav class="sidebar-nav">
        <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}" data-page-link @if(request()->routeIs('dashboard')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="5" rx="1.5"/><rect x="13.5" y="11.5" width="7" height="9" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/></svg>
            <span>Dashboard</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('profile') ? 'is-active' : '' }}" href="{{ route('profile') }}" data-page-link @if(request()->routeIs('profile')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
            <span>Profile</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('settings') ? 'is-active' : '' }}" href="{{ route('settings') }}" data-page-link @if(request()->routeIs('settings')) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="6.4"/><g><rect x="10.25" y="1.5" width="3.5" height="5" rx="1.2"/><rect x="10.25" y="1.5" width="3.5" height="5" rx="1.2" transform="rotate(45 12 12)"/><rect x="10.25" y="1.5" width="3.5" height="5" rx="1.2" transform="rotate(90 12 12)"/><rect x="10.25" y="1.5" width="3.5" height="5" rx="1.2" transform="rotate(135 12 12)"/><rect x="10.25" y="1.5" width="3.5" height="5" rx="1.2" transform="rotate(180 12 12)"/><rect x="10.25" y="1.5" width="3.5" height="5" rx="1.2" transform="rotate(225 12 12)"/><rect x="10.25" y="1.5" width="3.5" height="5" rx="1.2" transform="rotate(270 12 12)"/><rect x="10.25" y="1.5" width="3.5" height="5" rx="1.2" transform="rotate(315 12 12)"/></g><circle cx="12" cy="12" r="3.2"/></svg>
            <span>Settings</span>
        </a>
        @if (auth()->user()->isAdmin())
            <a class="sidebar-link {{ request()->routeIs('students.*') ? 'is-active' : '' }}" href="{{ route('students.index') }}" data-page-link @if(request()->routeIs('students.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="16.5" cy="9" r="2.5"/><path d="M16.5 14.5a5 5 0 0 1 5 5.5"/></svg>
                <span>Students</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('lecturers.*') ? 'is-active' : '' }}" href="{{ route('lecturers.index') }}" data-page-link @if(request()->routeIs('lecturers.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                <span>Lecturers</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('courses.*') ? 'is-active' : '' }}" href="{{ route('courses.index') }}" data-page-link @if(request()->routeIs('courses.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 5h7a3 3 0 0 1 3 3v12a2 2 0 0 0-2-2H2z"/><path d="M22 5h-7a3 3 0 0 0-3 3v12a2 2 0 0 1 2-2h8z"/></svg>
                <span>Courses</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('grades.*') ? 'is-active' : '' }}" href="{{ route('grades.index') }}" data-page-link @if(request()->routeIs('grades.*')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 9h8"/><path d="M8 13h4"/><path d="M13 16l1.5 1.5 3-3"/></svg>
                <span>Grades</span>
            </a>
        @else
            <a class="sidebar-link {{ request()->routeIs('student.courses') ? 'is-active' : '' }}" href="{{ route('student.courses') }}" data-page-link @if(request()->routeIs('student.courses')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 5h7a3 3 0 0 1 3 3v12a2 2 0 0 0-2-2H2z"/><path d="M22 5h-7a3 3 0 0 0-3 3v12a2 2 0 0 1 2-2h8z"/></svg>
                <span>My courses</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('student.grades') ? 'is-active' : '' }}" href="{{ route('student.grades') }}" data-page-link @if(request()->routeIs('student.grades')) aria-current="page" @endif>
                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 9h8"/><path d="M8 13h4"/><path d="M13 16l1.5 1.5 3-3"/></svg>
                <span>My grades</span>
            </a>
        @endif
    </nav>

    <div class="sidebar-bottom">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="sidebar-link sidebar-logout" type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                <span>Log out</span>
            </button>
        </form>
    </div>
</aside>
