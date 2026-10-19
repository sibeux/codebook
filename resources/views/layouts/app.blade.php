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

    <link rel="stylesheet" href="{{ asset('css/codebook.css') }}">
    @yield('styles')
</head>
<body>

    @yield('content')

    <script>
        // Global Theme initialization
        (function() {
            const savedTheme = localStorage.getItem('codebook_theme');
            const hljsDark = document.getElementById('hljs-dark');
            const hljsLight = document.getElementById('hljs-light');

            if (savedTheme === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
                if (hljsDark && hljsLight) {
                    hljsDark.disabled = true;
                    hljsLight.disabled = false;
                }
            }
        })();
    </script>
    @yield('scripts')
</body>
</html>

