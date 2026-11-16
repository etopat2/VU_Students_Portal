<aside class="sidebar" id="appSidebar" tabindex="-1" aria-label="Main navigation">
    <a class="sidebar-brand" href="{{ route('dashboard') }}" data-page-link>
        <img class="brand-mark" src="{{ asset('images/vu-logo.png') }}" alt="">
        <span>
            <span class="brand-name">VU Student</span>
            <span class="brand-caption">PORTAL</span>
        </span>
    </a>
    <button class="btn-close btn-close-white sidebar-close d-lg-none" type="button" aria-label="Close navigation"></button>

    <p class="sidebar-label">{{ auth()->user()->isAdmin() ? 'Administration' : 'Workspace' }}</p>
    <nav class="sidebar-nav">
        <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}" data-page-link @if(request()->routeIs('dashboard')) aria-current="page" @endif>
            <i class="fa fa-dashboard" aria-hidden="true"></i>
            <span>Dashboard</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('profile') ? 'is-active' : '' }}" href="{{ route('profile') }}" data-page-link @if(request()->routeIs('profile')) aria-current="page" @endif>
            <i class="fa fa-user" aria-hidden="true"></i>
            <span>Profile</span>
        </a>
        <a class="sidebar-link {{ request()->routeIs('settings') ? 'is-active' : '' }}" href="{{ route('settings') }}" data-page-link @if(request()->routeIs('settings')) aria-current="page" @endif>
            <i class="fa fa-cogs" aria-hidden="true"></i>
            <span>Account settings</span>
        </a>

        @if (auth()->user()->hasAnyRole(['admin', 'registrar']))
            <p class="sidebar-label">Institution setup</p>
            @foreach ([
                'faculties' => ['label' => 'Faculties', 'icon' => 'university'],
                'departments' => ['label' => 'Departments', 'icon' => 'sitemap'],
                'study-programs' => ['label' => 'Study programs', 'icon' => 'calendar'],
                'modules' => ['label' => 'Modules', 'icon' => 'cubes'],
            ] as $type => $label)
                <a class="sidebar-link {{ request()->routeIs('academic-structure.*') && request()->route('type') === $type ? 'is-active' : '' }}" href="{{ route('academic-structure.index', $type) }}" data-page-link>
                    <i class="fa fa-{{ $label['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $label['label'] }}</span>
                </a>
            @endforeach
            <a class="sidebar-link {{ request()->routeIs('study-sessions.index', 'study-sessions.create', 'study-sessions.edit') ? 'is-active' : '' }}" href="{{ route('study-sessions.index') }}" data-page-link>
                <i class="fa fa-clock-o" aria-hidden="true"></i>
                <span>Study sessions</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('study-session-requests.*') ? 'is-active' : '' }}" href="{{ route('study-session-requests.index') }}" data-page-link>
                <i class="fa fa-exchange" aria-hidden="true"></i>
                <span>Session change requests</span>
            </a>
            @if (auth()->user()->isAdmin())
                <a class="sidebar-link {{ request()->routeIs('study-sessions.assignments') ? 'is-active' : '' }}" href="{{ route('study-sessions.assignments') }}" data-page-link>
                    <i class="fa fa-sliders" aria-hidden="true"></i>
                    <span>Strategic assignments</span>
                </a>
            @endif
        @endif

        @if (auth()->user()->hasRole('registrar'))
            <p class="sidebar-label">Academic records</p>
            <a class="sidebar-link {{ request()->routeIs('students.*') || request()->routeIs('semester-registrations.*') ? 'is-active' : '' }}" href="{{ route('students.index') }}" data-page-link>
                <i class="fa fa-users" aria-hidden="true"></i>
                <span>Students</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('lecturers.*') ? 'is-active' : '' }}" href="{{ route('lecturers.index') }}" data-page-link>
                <i class="fa fa-graduation-cap" aria-hidden="true"></i>
                <span>Lecturers</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('courses.*') ? 'is-active' : '' }}" href="{{ route('courses.index') }}" data-page-link>
                <i class="fa fa-book" aria-hidden="true"></i>
                <span>Courses</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('grades.*') ? 'is-active' : '' }}" href="{{ route('grades.index') }}" data-page-link>
                <i class="fa fa-file-text-o" aria-hidden="true"></i>
                <span>Legacy grade records</span>
            </a>
        @endif

        @if (auth()->user()->hasAnyRole(['lecturer', 'registrar']))
            <p class="sidebar-label">Teaching</p>
            <a class="sidebar-link {{ request()->routeIs('assessments.*') || request()->routeIs('assessment-results.*') ? 'is-active' : '' }}" href="{{ route('assessments.index') }}" data-page-link>
                <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                <span>Tests &amp; examinations</span>
            </a>
        @endif

        @if (auth()->user()->hasRole('accountant'))
            <p class="sidebar-label">Finance</p>
            <a class="sidebar-link {{ request()->routeIs('payments.*') ? 'is-active' : '' }}" href="{{ route('payments.index') }}" data-page-link>
                <i class="fa fa-credit-card" aria-hidden="true"></i>
                <span>Bank &amp; mobile payments</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('cash-management.*') ? 'is-active' : '' }}" href="{{ route('cash-management.index') }}" data-page-link>
                <i class="fa fa-money" aria-hidden="true"></i>
                <span>Cash management</span>
            </a>
        @endif

        @if (auth()->user()->hasAnyRole(['accountant', 'registrar']))
            <a class="sidebar-link {{ request()->routeIs('financial-statements.*') ? 'is-active' : '' }}" href="{{ route('financial-statements.index') }}" data-page-link>
                <i class="fa fa-file-text" aria-hidden="true"></i>
                <span>Student statements</span>
            </a>
        @endif

        @if (auth()->user()->isStudent())
            <p class="sidebar-label">My study</p>
            <a class="sidebar-link {{ request()->routeIs('student.study-session*') ? 'is-active' : '' }}" href="{{ route('student.study-session') }}" data-page-link>
                <i class="fa fa-clock-o" aria-hidden="true"></i>
                <span>Study session</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('student.courses') ? 'is-active' : '' }}" href="{{ route('student.courses') }}" data-page-link>
                <i class="fa fa-bookmark" aria-hidden="true"></i>
                <span>My courses</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('student.grades') ? 'is-active' : '' }}" href="{{ route('student.grades') }}" data-page-link>
                <i class="fa fa-line-chart" aria-hidden="true"></i>
                <span>My results</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('student-assessments.*') ? 'is-active' : '' }}" href="{{ route('student-assessments.index') }}" data-page-link>
                <i class="fa fa-check-square-o" aria-hidden="true"></i>
                <span>Tests &amp; examinations</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('financial-statements.mine') ? 'is-active' : '' }}" href="{{ route('financial-statements.mine') }}" data-page-link>
                <i class="fa fa-calculator" aria-hidden="true"></i>
                <span>My financial statement</span>
            </a>
        @endif

        @if (auth()->user()->isAdmin())
            <p class="sidebar-label">System administration</p>
            <a class="sidebar-link {{ request()->routeIs('system-settings.*') ? 'is-active' : '' }}" href="{{ route('system-settings.edit') }}" data-page-link>
                <i class="fa fa-sliders" aria-hidden="true"></i>
                <span>Institution settings</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('users.*') ? 'is-active' : '' }}" href="{{ route('users.index') }}" data-page-link>
                <i class="fa fa-user-plus" aria-hidden="true"></i>
                <span>Users &amp; roles</span>
            </a>
            <a class="sidebar-link {{ request()->routeIs('audit-logs.*') ? 'is-active' : '' }}" href="{{ route('audit-logs.index') }}" data-page-link>
                <i class="fa fa-history" aria-hidden="true"></i>
                <span>Audit logs</span>
            </a>
        @endif
    </nav>

    <div class="sidebar-bottom">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button class="sidebar-link sidebar-logout" type="submit">
                <i class="fa fa-sign-out" aria-hidden="true"></i>
                <span>Log out</span>
            </button>
        </form>
    </div>
</aside>
