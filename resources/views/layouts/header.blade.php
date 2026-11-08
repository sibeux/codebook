<header>
    <div class="brand">
        <button type="button" class="icon-btn sidebar-toggle-btn" id="sidebar-toggle-btn" title="Buka / Tutup Sidebar (Ctrl+B)" aria-label="Toggle Sidebar">
            <svg class="icon-sidebar" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="9" y1="3" x2="9" y2="21"></line>
            </svg>
        </button>

        <a href="{{ route('workspace') }}" class="brand-logo" title="Codebook">
            <h1>Codebook</h1>
        </a>

        @if (!empty($detailOnly))
            <a href="{{ route('todos.index', request()->only(['workspace_id', 'project_id', 'status'])) }}" class="icon-btn header-back-btn" title="Kembali ke Board">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <span>Board</span>
            </a>
        @endif

        <nav class="header-nav-tabs" aria-label="Navigasi Modul">
            <a href="{{ route('workspace') }}" class="header-tab {{ request()->routeIs('workspace*') ? 'active' : '' }}" title="Catatan & Dokumen Markdown">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>Notes</span>
            </a>
            <a href="{{ route('todos.index') }}" class="header-tab {{ request()->routeIs('todos*') ? 'active' : '' }}" title="Tasks & Projects">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11l3 3L22 4"></path>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                </svg>
                <span>Tasks</span>
            </a>
        </nav>
    </div>

    <div class="header-actions">
        @auth
            <div class="user-badge" title="Pengguna: {{ Auth::user()->name }}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>{{ Auth::user()->name }}</span>
                @if (Auth::user()->role)
                    <span class="user-role-badge">{{ Auth::user()->role }}</span>
                @endif
            </div>
        @endauth

        <button type="button" class="icon-btn" id="theme-btn" title="Ganti Mode Tema">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"></circle>
                <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path>
            </svg>
            <span>Theme</span>
        </button>

        @auth
            <form action="{{ route('logout') }}" method="POST" style="margin:0;">
                @csrf
                <button type="submit" class="icon-btn" title="Keluar dari akun">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                </button>
            </form>
        @endauth
    </div>
</header>
