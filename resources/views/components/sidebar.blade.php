<aside id="sidebar" class="w-64 bg-[#1A1A2E] flex flex-col h-full fixed lg:static inset-y-0 left-0 z-50 -translate-x-full lg:translate-x-0 transition-transform duration-300">

    {{-- Logo / Brand --}}
    <div class="p-6 border-b border-white/10">
        <div class="flex items-center gap-3">
            <span class="text-2xl">{{ config('app_settings.logo_icon') }}</span>
            <div>
                <h2 class="text-white font-heading font-bold text-lg leading-tight">{{ config('app_settings.name') }}</h2>
                <p class="text-yellow-400/70 text-xs">{{ config('app_settings.tagline') }}</p>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">

        @if(auth()->user()->hasRole('admin'))
            <p class="text-gray-500 text-xs uppercase tracking-widest px-4 pt-2 pb-1">Main</p>

            <a href="{{ route('admin.dashboard') }}"
               class="{{ request()->routeIs('admin.dashboard') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                Dashboard
            </a>

            <p class="text-gray-500 text-xs uppercase tracking-widest px-4 pt-4 pb-1">Manage</p>

            <a href="{{ route('admin.exams.index') }}"
               class="{{ request()->routeIs('admin.exams.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Exams
            </a>

            <a href="{{ route('admin.students.index') }}"
               class="{{ request()->routeIs('admin.students.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
                Students
            </a>

            <a href="{{ route('admin.parents.index') }}"
               class="{{ request()->routeIs('admin.parents.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                <span class="flex-1 min-w-0 truncate">Parents</span>
                @if(($pendingParentRequestsCount ?? 0) > 0)
                    <span class="ml-auto shrink-0 inline-flex min-h-[1.125rem] items-center justify-center rounded px-1.5 py-0.5 text-[10px] font-semibold tabular-nums leading-none text-white bg-rose-600 shadow-sm ring-1 ring-white/10"
                          title="Pending parent registration requests">
                        {{ $pendingParentRequestsCount > 99 ? '99+' : $pendingParentRequestsCount }}
                    </span>
                @endif
            </a>

            <a href="{{ route('admin.results.index') }}"
               class="{{ request()->routeIs('admin.results.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Results
            </a>

            <p class="text-gray-500 text-xs uppercase tracking-widest px-4 pt-4 pb-1">Account</p>

            <a href="{{ route('admin.profile.edit') }}"
               class="{{ request()->routeIs('admin.profile.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                My Profile
            </a>

        @elseif(auth()->user()->hasRole('parent'))
            <p class="text-gray-500 text-xs uppercase tracking-widest px-4 pt-2 pb-1">Main</p>

            <a href="{{ route('parent.dashboard') }}"
               class="{{ request()->routeIs('parent.dashboard') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                Dashboard
            </a>

            <p class="text-gray-500 text-xs uppercase tracking-widest px-4 pt-4 pb-1">My Children</p>

            <a href="{{ route('parent.students.index') }}"
               class="{{ request()->routeIs('parent.students.index') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                My Children
            </a>

            <a href="{{ route('parent.students.create') }}"
               class="{{ request()->routeIs('parent.students.create') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Add Student
            </a>

            <p class="text-gray-500 text-xs uppercase tracking-widest px-4 pt-4 pb-1">Account</p>

            <a href="{{ route('parent.profile.edit') }}"
               class="{{ request()->routeIs('parent.profile.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                My Profile
            </a>

        @elseif(auth()->user()->hasRole('student'))
            <p class="text-gray-500 text-xs uppercase tracking-widest px-4 pt-2 pb-1">Main</p>

            <a href="{{ route('student.dashboard') }}"
               class="{{ request()->routeIs('student.dashboard') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                Dashboard
            </a>

            <p class="text-gray-500 text-xs uppercase tracking-widest px-4 pt-4 pb-1">Exams</p>

            <a href="{{ route('student.exams.index') }}"
               class="{{ request()->routeIs('student.exams.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Available Exams
            </a>

            <a href="{{ route('student.results.index') }}"
               class="{{ request()->routeIs('student.results.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                My Results
            </a>
        @endif

    </nav>

    {{-- User Info + Logout --}}
    <div class="p-4 border-t border-white/10">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 rounded-full bg-yellow-400 flex items-center justify-center text-gray-900 font-bold text-sm shrink-0">
                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-yellow-400/70 text-xs capitalize">
                    {{ auth()->user()->getRoleNames()->first() ?? 'user' }}
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full flex items-center gap-2 text-gray-400 hover:text-white text-sm px-3 py-2 rounded-lg hover:bg-white/10 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Logout
            </button>
        </form>
    </div>

</aside>
