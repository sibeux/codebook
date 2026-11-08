<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="dark light">
    <title>{{ config('app.name', 'Codebook') }} - @yield('title', 'Workspace')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Marked.js, DOMPurify, Highlight.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/11.1.1/marked.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js" defer></script>

    <!-- Code Syntax Highlighting Themes -->
    <link id="hljs-dark" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-dark.min.css">
    <link id="hljs-light" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/atom-one-light.min.css" disabled>

    <link rel="stylesheet" href="{{ asset('css/codebook.css') }}?v={{ file_exists(public_path('css/codebook.css')) ? filemtime(public_path('css/codebook.css')) : time() }}">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('codebook_theme');
            const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
            if (savedTheme === 'light' || (!savedTheme && prefersLight)) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
            if (localStorage.getItem('codebook_sidebar_collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
            const savedFontSize = localStorage.getItem('codebook_font_size');
            if (savedFontSize) {
                document.documentElement.style.setProperty('--chat-font-size', savedFontSize + 'px');
            }
        })();
    </script>
    @yield('styles')
</head>
<body>
    <div id="global-page-progress" class="global-page-progress" role="progressbar" aria-hidden="true" style="--page-load-progress: 0;"></div>

    @yield('content')

    <script>
        // Synchronize highlight.js themes with initial html theme attribute
        (function() {
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            const hljsDark = document.getElementById('hljs-dark');
            const hljsLight = document.getElementById('hljs-light');
            if (isLight && hljsDark && hljsLight) {
                hljsDark.disabled = true;
                hljsLight.disabled = false;
            }
        })();

        // Global Header Theme Toggle Handling
        (function() {
            const themeBtn = document.getElementById('theme-btn');
            if (!themeBtn || themeBtn.dataset.themeBound) return;
            themeBtn.dataset.themeBound = 'true';

            const hljsDark = document.getElementById('hljs-dark');
            const hljsLight = document.getElementById('hljs-light');

            function updateThemeBtn() {
                const isLight = document.documentElement.getAttribute('data-theme') === 'light';
                if (isLight) {
                    themeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg> <span>Light Mode</span>';
                    themeBtn.setAttribute('title', 'Tema saat ini: Light Mode. Klik untuk beralih ke Dark Mode');
                } else {
                    themeBtn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg> <span>Dark Mode</span>';
                    themeBtn.setAttribute('title', 'Tema saat ini: Dark Mode. Klik untuk beralih ke Light Mode');
                }
            }

            updateThemeBtn();

            themeBtn.addEventListener('click', function() {
                const isLight = document.documentElement.getAttribute('data-theme') === 'light';
                if (isLight) {
                    document.documentElement.removeAttribute('data-theme');
                    localStorage.setItem('codebook_theme', 'dark');
                    if (hljsDark && hljsLight) {
                        hljsDark.disabled = false;
                        hljsLight.disabled = true;
                    }
                } else {
                    document.documentElement.setAttribute('data-theme', 'light');
                    localStorage.setItem('codebook_theme', 'light');
                    if (hljsDark && hljsLight) {
                        hljsDark.disabled = true;
                        hljsLight.disabled = false;
                    }
                }
                updateThemeBtn();
            });
        })();

        // Global Sidebar Toggle Handling (Notes & Todos)
        (function() {
            const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
            if (!sidebarToggleBtn || sidebarToggleBtn.dataset.sidebarBound) return;
            sidebarToggleBtn.dataset.sidebarBound = 'true';

            function updateSidebarToggleAria(isCollapsed) {
                if (sidebarToggleBtn) {
                    sidebarToggleBtn.setAttribute('title', isCollapsed ? 'Buka Sidebar (Ctrl+B)' : 'Tutup Sidebar (Ctrl+B)');
                    sidebarToggleBtn.setAttribute('aria-expanded', !isCollapsed);
                }
            }

            function toggleSidebar(forceState) {
                const isCurrentlyCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
                const shouldCollapse = (typeof forceState === 'boolean') ? forceState : !isCurrentlyCollapsed;

                if (shouldCollapse) {
                    document.documentElement.classList.add('sidebar-collapsed');
                    localStorage.setItem('codebook_sidebar_collapsed', 'true');
                } else {
                    document.documentElement.classList.remove('sidebar-collapsed');
                    localStorage.setItem('codebook_sidebar_collapsed', 'false');
                }

                updateSidebarToggleAria(shouldCollapse);
            }

            updateSidebarToggleAria(document.documentElement.classList.contains('sidebar-collapsed'));

            document.addEventListener('click', function(e) {
                if (e.target.closest('#sidebar-toggle-btn')) {
                    toggleSidebar();
                    return;
                }
                if (e.target.closest('#sidebar-floating-toggle')) {
                    toggleSidebar(false);
                    return;
                }
                if (e.target.closest('#sidebar-close-btn')) {
                    toggleSidebar(true);
                    return;
                }
            });

            document.addEventListener('keydown', function(e) {
                const isCtrlOrCmd = e.ctrlKey || e.metaKey;
                if (!isCtrlOrCmd) return;

                if (e.key === '\\') {
                    e.preventDefault();
                    toggleSidebar();
                    return;
                }

                if (e.key.toLowerCase() === 'b') {
                    const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
                    if (activeTag !== 'textarea' && activeTag !== 'input') {
                        e.preventDefault();
                        toggleSidebar();
                    }
                }
            });
        })();

        // Global Page Navigation Feedback (Instant Tab Active & Top Progress Bar)
        (function() {
            const progressBar = document.getElementById('global-page-progress');
            let progressTimer = null;
            let currentProgress = 0;

            function startProgress() {
                if (!progressBar) return;
                window.clearInterval(progressTimer);
                currentProgress = 0.18;
                progressBar.style.setProperty('--page-load-progress', String(currentProgress));
                progressBar.classList.add('visible');

                progressTimer = window.setInterval(() => {
                    if (currentProgress < 0.86) {
                        currentProgress += (0.86 - currentProgress) * 0.14;
                        progressBar.style.setProperty('--page-load-progress', String(currentProgress));
                    }
                }, 130);
            }

            function stopProgress() {
                if (!progressBar) return;
                window.clearInterval(progressTimer);
                currentProgress = 1;
                progressBar.style.setProperty('--page-load-progress', '1');
                window.setTimeout(() => {
                    progressBar.classList.remove('visible');
                    progressBar.style.setProperty('--page-load-progress', '0');
                }, 220);
            }

            // Restore state if page was restored from bfcache
            window.addEventListener('pageshow', function() {
                stopProgress();
                document.querySelectorAll('.header-tab.is-navigating').forEach(tab => {
                    tab.classList.remove('is-navigating');
                });
            });

            window.addEventListener('beforeunload', function() {
                if (progressBar && progressBar.classList.contains('visible')) {
                    progressBar.style.setProperty('--page-load-progress', '1');
                }
            });

            document.addEventListener('click', function(e) {
                const tab = e.target.closest('.header-tab');
                const backBtn = e.target.closest('.header-back-btn');
                const brandLogo = e.target.closest('.brand-logo');
                const target = tab || backBtn || brandLogo;

                if (!target) return;

                // Ignore clicks with modifiers or opening in new window/tab
                if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0 || target.getAttribute('target') === '_blank') {
                    return;
                }

                if (tab) {
                    if (tab.classList.contains('active')) return;
                    const nav = tab.closest('.header-nav-tabs');
                    if (nav) {
                        nav.querySelectorAll('.header-tab').forEach(t => t.classList.remove('active', 'is-navigating'));
                    }
                    tab.classList.add('active', 'is-navigating');
                }

                startProgress();
            });
        })();
    </script>
    @yield('scripts')
</body>
</html>

