<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Codebook') }} - @yield('title', 'Workspace')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Marked.js, DOMPurify, Highlight.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/marked/11.1.1/marked.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>

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
        })();
    </script>
    @yield('styles')
</head>
<body>

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
    </script>
    @yield('scripts')
</body>
</html>

