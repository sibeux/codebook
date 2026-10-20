@extends('layouts.app')

@section('title', $note->title . ' (Shared)')

@section('styles')
<style>
    .share-container {
        max-width: 960px;
        margin: 0 auto;
        padding: 24px 20px 80px;
        width: 100%;
        display: flex;
        flex-direction: column;
        min-height: calc(100vh - 56px);
    }
    .share-header-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
    }
    .share-note-title {
        font-size: 22px;
        font-weight: 700;
        letter-spacing: -0.3px;
        margin: 0 0 10px 0;
        color: var(--text);
        word-break: break-word;
    }
    .share-meta-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        font-size: 12px;
        color: var(--muted);
    }
    .share-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .share-badge-public {
        background: rgba(34, 197, 94, 0.12);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.28);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .share-badge-private {
        background: rgba(234, 179, 8, 0.12);
        color: #eab308;
        border: 1px solid rgba(234, 179, 8, 0.28);
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .share-thread-container {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 12px;
        padding: 24px;
        flex: 1;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }
    .share-thread-tools {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 14px;
        border-bottom: 1px solid var(--border);
    }
    #share-thread {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }
    /* Shared thread overrides for clean read-only view */
    #share-thread .row {
        display: flex;
        width: 100%;
    }
    #share-thread .row.me {
        justify-content: flex-end;
    }
    #share-thread .row.other {
        justify-content: flex-start;
    }
    #share-thread .bubble-wrap {
        max-width: 82%;
        display: flex;
        flex-direction: column;
    }
    #share-thread .row.me .bubble-wrap {
        align-items: flex-end;
    }
    #share-thread .row.other .bubble-wrap {
        align-items: flex-start;
    }
</style>
@endsection

@section('content')
<header>
    <div class="brand">
        <a href="{{ route('workspace') }}" style="text-decoration:none;color:inherit;display:inline-flex;align-items:center;gap:8px;">
            <h1>Codebook</h1>
        </a>
        <span>Shared Note</span>
    </div>

    <div class="header-actions">
        <button class="icon-btn" id="theme-btn" title="Ganti Mode Tema"><span>Light mode</span></button>
        @auth
            <div class="user-badge">
                <span>&#128100; {{ Auth::user()->name }}</span>
            </div>
            @if (Auth::id() === $note->user_id)
                <a href="{{ route('workspace', ['note' => $note->id]) }}" class="icon-btn btn-primary" style="text-decoration:none;" title="Buka di Workspace">
                    <span>Buka di Workspace &rarr;</span>
                </a>
            @endif
        @else
            <a href="{{ route('login') }}" class="icon-btn" style="text-decoration:none;" title="Masuk ke Akun">
                <span>Login</span>
            </a>
        @endauth
    </div>
</header>

<main style="display:block;overflow-y:auto;background:var(--bg);">
    <div class="share-container">
        {{-- Note Info Card --}}
        <div class="share-header-card">
            <h2 class="share-note-title">{{ $note->title }}</h2>
            <div class="share-meta-bar">
                <div class="share-meta-item">
                    <span>&#128100;</span>
                    <span>Pemilik: <strong>{{ $note->user ? $note->user->name : 'Pengguna' }}</strong></span>
                </div>
                <span>&bull;</span>
                <div class="share-meta-item">
                    <span>&#128197;</span>
                    <span>Diperbarui: {{ $note->updated_at->format('d M Y, H:i') }}</span>
                </div>
                <span>&bull;</span>
                <div class="share-meta-item">
                    @if ($note->is_public)
                        <span class="share-badge-public">&#127760; Publik (Dapat diakses tanpa login)</span>
                    @else
                        <span class="share-badge-private">&#128274; Pribadi (Hanya Pemilik)</span>
                    @endif
                </div>
                <span>&bull;</span>
                <div class="share-meta-item">
                    <span>{{ $note->messages->count() }} pesan</span>
                </div>
                <div style="margin-left:auto;">
                    <button type="button" class="icon-btn" id="share-copy-link-btn" onclick="copyShareLinkDirect()" style="padding:4px 10px;font-size:11.5px;">
                        <span id="share-copy-text">&#128279; Salin Tautan</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Note Content Thread --}}
        <div class="share-thread-container">
            <div class="share-thread-tools">
                <span style="font-size:12px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;">
                    Isi Catatan (Markdown Mode)
                </span>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:11px;color:var(--muted);">Ukuran Font:</span>
                    <input type="range" id="font-size" min="12" max="20" step="0.5" value="14.5" style="width:90px;">
                    <span id="font-size-out" style="font-size:11.5px;color:var(--muted);min-width:38px;">14.5px</span>
                </div>
            </div>

            @if ($note->messages->count() === 0)
                <div class="empty-state" style="padding:40px 20px;">
                    <div class="glyph">&gt;_</div>
                    <p>Catatan <strong>"{{ $note->title }}"</strong> belum memiliki pesan atau konten markdown.</p>
                </div>
            @else
                <div id="share-thread">
                    {{-- Messages rendered by JS --}}
                </div>
            @endif
        </div>
    </div>
</main>
@endsection

@section('scripts')
<script>
    const MESSAGES_DATA = @json($note->messages);

    function copyShareLinkDirect() {
        const url = window.location.href;
        const btn = document.getElementById('share-copy-link-btn');
        const textSpan = document.getElementById('share-copy-text');

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(showSuccess).catch(fallback);
        } else {
            fallback();
        }

        function fallback() {
            const ta = document.createElement('textarea');
            ta.value = url;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                showSuccess();
            } finally {
                document.body.removeChild(ta);
            }
        }

        function showSuccess() {
            if (textSpan) textSpan.textContent = '✓ Tersalin!';
            if (btn) btn.style.borderColor = 'var(--primary)';
            setTimeout(() => {
                if (textSpan) textSpan.innerHTML = '&#128279; Salin Tautan';
                if (btn) btn.style.borderColor = '';
            }, 2000);
        }
    }

    // Code blocks setup (syntax highlighting, copy buttons, language badge)
    function setupCodeBlocks(container) {
        if (!container) return;
        container.querySelectorAll('pre').forEach(function (pre) {
            if (pre.parentElement && pre.parentElement.classList.contains('code-block-wrap')) {
                return;
            }

            const code = pre.querySelector('code');
            if (!code) return;

            if (!code.classList.contains('hljs') && window.hljs) {
                hljs.highlightElement(code);
            }

            let langName = '';
            const langMatch = code.className.match(/\blanguage-([a-zA-Z0-9_-]+)\b/i);
            if (langMatch && langMatch[1]) {
                const rawLang = langMatch[1].toLowerCase();
                if (rawLang !== 'undefined' && rawLang !== 'hljs') {
                    langName = rawLang;
                }
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'code-block-wrap' + (langName ? ' has-lang' : '');

            pre.parentNode.insertBefore(wrapper, pre);
            wrapper.appendChild(pre);

            const tools = document.createElement('div');
            tools.className = 'code-block-tools';

            if (langName) {
                const langBadge = document.createElement('span');
                langBadge.className = 'code-lang-badge';
                langBadge.textContent = langName;
                tools.appendChild(langBadge);
            }

            const copyBtn = document.createElement('button');
            copyBtn.type = 'button';
            copyBtn.className = 'code-copy-btn';
            copyBtn.setAttribute('title', 'Salin kode');
            copyBtn.setAttribute('aria-label', 'Salin kode');
            copyBtn.innerHTML = `
                <svg class="copy-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                <svg class="check-icon" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            `;

            copyBtn.addEventListener('click', function () {
                const textToCopy = code.innerText || code.textContent || '';
                navigator.clipboard.writeText(textToCopy).then(function () {
                    const copyIcon = copyBtn.querySelector('.copy-icon');
                    const checkIcon = copyBtn.querySelector('.check-icon');
                    if (copyIcon && checkIcon) {
                        copyIcon.style.display = 'none';
                        checkIcon.style.display = 'block';
                        copyBtn.classList.add('copied');
                        setTimeout(function () {
                            copyIcon.style.display = 'block';
                            checkIcon.style.display = 'none';
                            copyBtn.classList.remove('copied');
                        }, 2000);
                    }
                });
            });

            tools.appendChild(copyBtn);
            wrapper.appendChild(tools);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        marked.setOptions({ breaks: true, gfm: true });

        // Theme toggle
        const themeBtn = document.getElementById('theme-btn');
        const hljsDark = document.getElementById('hljs-dark');
        const hljsLight = document.getElementById('hljs-light');

        function updateThemeButton() {
            const isLight = document.documentElement.getAttribute('data-theme') === 'light';
            if (themeBtn) {
                themeBtn.innerHTML = isLight ? '<span>Dark mode</span>' : '<span>Light mode</span>';
            }
            if (hljsDark && hljsLight) {
                hljsDark.disabled = isLight;
                hljsLight.disabled = !isLight;
            }
        }

        if (themeBtn) {
            themeBtn.addEventListener('click', function () {
                const current = document.documentElement.getAttribute('data-theme');
                const next = current === 'light' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-theme', next);
                localStorage.setItem('codebook_theme', next);
                updateThemeButton();
            });
            updateThemeButton();
        }

        // Font size slider
        const fontSlider = document.getElementById('font-size');
        const fontOut = document.getElementById('font-size-out');
        const thread = document.getElementById('share-thread');

        if (fontSlider && fontOut) {
            const savedSize = localStorage.getItem('codebook_font_size') || '14.5';
            fontSlider.value = savedSize;
            fontOut.textContent = savedSize + 'px';
            document.documentElement.style.setProperty('--chat-font-size', savedSize + 'px');
            if (thread) {
                thread.style.setProperty('--chat-font-size', savedSize + 'px');
            }

            fontSlider.addEventListener('input', function () {
                const val = this.value;
                fontOut.textContent = val + 'px';
                document.documentElement.style.setProperty('--chat-font-size', val + 'px');
                if (thread) {
                    thread.style.setProperty('--chat-font-size', val + 'px');
                }
                localStorage.setItem('codebook_font_size', val);
            });
        }

        // Render messages
        if (thread && Array.isArray(MESSAGES_DATA)) {
            MESSAGES_DATA.forEach(function (m) {
                const row = document.createElement('div');
                row.className = 'row ' + (m.sender === 'me' ? 'me' : 'other');

                const wrap = document.createElement('div');
                wrap.className = 'bubble-wrap';

                const meta = document.createElement('div');
                meta.className = 'meta';
                const displayTime = m.sent_time || (m.created_at ? new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '');
                meta.innerHTML = '<span class="who">' + (m.sender === 'me' ? 'Me' : 'Other') + '</span><span>' + displayTime + '</span>';

                const bubble = document.createElement('div');
                bubble.className = 'bubble';

                const mdWrap = document.createElement('div');
                mdWrap.className = 'md-wrap';

                const mdDiv = document.createElement('div');
                mdDiv.className = 'md';
                const rawHtml = marked.parse(m.content || '');
                mdDiv.innerHTML = DOMPurify.sanitize(rawHtml);
                mdDiv.querySelectorAll('pre code').forEach(function (block) {
                    hljs.highlightElement(block);
                });
                setupCodeBlocks(mdDiv);

                const fade = document.createElement('div');
                fade.className = 'fade';

                mdWrap.appendChild(mdDiv);
                mdWrap.appendChild(fade);
                bubble.appendChild(mdWrap);

                const contentStr = m.content || '';
                const lineCount = contentStr.split('\n').length;
                const charCount = contentStr.length;

                const tools = document.createElement('div');
                tools.className = 'bubble-tools';
                tools.innerHTML = '<span class="linecount">' + lineCount + ' baris &middot; ' + charCount + ' karakter</span>' +
                    '<button class="toggle-btn" id="toggle-' + m.id + '">&#8964; tampilkan semua</button>';

                bubble.appendChild(tools);
                wrap.appendChild(meta);
                wrap.appendChild(bubble);
                row.appendChild(wrap);
                thread.appendChild(row);

                // Auto-collapse check
                requestAnimationFrame(function () {
                    const scrollH = mdDiv.scrollHeight;
                    const maxH = 460;
                    const toggleBtn = tools.querySelector('.toggle-btn');
                    if (scrollH > maxH + 40) {
                        mdWrap.classList.add('collapsed');
                        if (toggleBtn) {
                            toggleBtn.style.display = 'inline-block';
                            toggleBtn.addEventListener('click', function () {
                                const isCollapsed = mdWrap.classList.toggle('collapsed');
                                toggleBtn.innerHTML = isCollapsed ? '&#8964; tampilkan semua' : '&#8963; ciutkan';
                            });
                        }
                    } else if (toggleBtn) {
                        toggleBtn.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
@endsection
