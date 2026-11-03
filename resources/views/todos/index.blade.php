@extends('layouts.app')

@section('title', 'Tasks & Projects')

@section('styles')
<style>
    /* Reset & Layout Shell */
    .todo-shell {
        display: flex;
        height: calc(100vh - 46px);
        width: 100vw;
        overflow: hidden;
        background: var(--bg);
        color: var(--text);
        font-family: 'Inter', -apple-system, sans-serif;
    }

    /* Column 1: Projects Sidebar */
    .pane-projects {
        width: 230px;
        min-width: 210px;
        background: var(--panel);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    /* Column 2: Task List */
    .pane-list {
        width: 380px;
        min-width: 320px;
        border-right: 1px solid var(--border);
        background: var(--panel-2);
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }

    /* Column 3: Active Task Workspace */
    .pane-workspace {
        flex: 1;
        background: var(--bg);
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }

    /* Shared Headers */
    .pane-header {
        height: 48px;
        padding: 0 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid var(--border);
        background: var(--panel);
        font-size: 13px;
        font-weight: 600;
        letter-spacing: -0.01em;
    }

    /* Project Items */
    .project-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 7px 12px;
        margin: 2px 8px;
        border-radius: 6px;
        font-size: 13px;
        color: var(--text-muted);
        text-decoration: none;
        transition: all 0.15s ease;
        cursor: pointer;
    }
    .project-link:hover {
        background: var(--hover-bg);
        color: var(--text);
    }
    .project-link.active {
        background: var(--hover-bg);
        color: var(--text);
        font-weight: 500;
        box-shadow: inset 2px 0 0 var(--accent);
    }
    .project-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 8px;
        display: inline-block;
        flex-shrink: 0;
    }

    /* Task Card in List */
    .task-card {
        padding: 10px 14px;
        border-bottom: 1px solid var(--border);
        cursor: pointer;
        transition: background 0.15s ease;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .task-card:hover {
        background: var(--hover-bg);
    }
    .task-card.active {
        background: var(--hover-bg);
        border-left: 3px solid var(--accent);
    }
    .task-checkbox {
        margin-top: 3px;
        cursor: pointer;
        accent-color: var(--accent);
        width: 15px;
        height: 15px;
    }
    .task-card.done .task-title-text {
        color: var(--text-muted);
    }
    .task-card.done .task-title-label {
        text-decoration: line-through;
        text-decoration-thickness: 2px;
    }
    .task-title-text {
        transition: color .2s ease;
    }
    .task-title-label {
        display: inline-block;
        position: relative;
    }
    .task-title-label::after {
        background: currentColor;
        content: '';
        height: 2px;
        left: 0;
        pointer-events: none;
        position: absolute;
        top: 52%;
        transform: scaleX(0);
        transform-origin: left center;
        transition: transform .22s cubic-bezier(.22, 1, .36, 1);
        width: 100%;
        z-index: 1;
    }
    .task-card.done .task-title-label::after {
        transform: scaleX(1);
    }
    .task-card-link {
        color: inherit;
        display: flex;
        flex: 1;
        min-width: 0;
        text-decoration: none;
    }

    /* Badges */
    .badge {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.03em;
    }
    .badge-urgent { background: rgba(239, 68, 68, 0.18); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
    .badge-high { background: rgba(249, 115, 22, 0.18); color: #fb923c; border: 1px solid rgba(249, 115, 22, 0.3); }
    .badge-medium { background: rgba(59, 130, 246, 0.18); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
    .badge-low { background: rgba(107, 114, 128, 0.18); color: #9ca3af; border: 1px solid rgba(107, 114, 128, 0.3); }

    /* Commands box */
    .cmd-box {
        background: var(--panel-2);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 8px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 8px;
        font-family: 'JetBrains Mono', monospace;
        font-size: 12px;
    }
    .cmd-copy-btn {
        background: var(--panel);
        border: 1px solid var(--border);
        color: var(--text-muted);
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        cursor: pointer;
        transition: all 0.15s;
    }
    .cmd-copy-btn:hover {
        color: var(--text);
        border-color: var(--accent);
    }

    /* Thread Stream */
    .thread-entry {
        background: var(--panel-2);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 12px;
    }
    .thread-time {
        font-size: 11px;
        color: var(--text-muted);
        margin-bottom: 6px;
        font-family: 'JetBrains Mono', monospace;
    }
    .thread-markdown pre {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 4px;
        padding: 8px;
        overflow-x: auto;
    }

    .todo-shell {
        background: linear-gradient(135deg, var(--bg), var(--panel));
    }
    .pane-projects, .pane-list, .pane-workspace {
        min-height: 0;
    }
    .pane-projects {
        box-shadow: 12px 0 28px rgba(0, 0, 0, 0.04);
        z-index: 2;
    }
    .pane-list {
        box-shadow: 12px 0 28px rgba(0, 0, 0, 0.03);
        z-index: 1;
    }
    .pane-header {
        backdrop-filter: blur(14px);
    }
    .task-card {
        position: relative;
        min-height: 58px;
    }
    .task-card::after {
        background: var(--accent);
        border-radius: 0 4px 4px 0;
        content: '';
        height: 24px;
        left: 0;
        opacity: 0;
        position: absolute;
        top: calc(50% - 12px);
        transition: opacity .15s ease;
        width: 3px;
    }
    .task-card.active { border-left: 0; }
    .task-card.active::after { opacity: 1; }
    .task-card-link:focus-visible, .project-link:focus-visible, .cmd-copy-btn:focus-visible {
        outline: 2px solid var(--accent);
        outline-offset: -2px;
    }
    .task-workspace-content {
        margin: 0 auto;
        padding: 32px;
    }
    .task-workspace-header {
        align-items: flex-start;
        border-bottom: 1px solid var(--border);
        display: flex;
        gap: 16px;
        justify-content: space-between;
        margin-bottom: 24px;
        padding-bottom: 20px;
    }
    .task-section {
        margin-bottom: 28px;
    }
    .task-section-heading {
        align-items: center;
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }
    .todo-modal-overlay {
        align-items: center;
        background: rgba(4, 8, 20, .68);
        backdrop-filter: blur(5px);
        display: none;
        inset: 0;
        justify-content: center;
        padding: 16px;
        position: fixed;
        z-index: 999;
    }
    .todo-modal {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 12px;
        box-shadow: 0 24px 72px rgba(0, 0, 0, .34);
        max-width: 420px;
        padding: 24px;
        width: min(100%, 420px);
    }
    .todo-modal h3 { margin: 0 0 18px; }
    .todo-modal input, .todo-modal textarea, .todo-modal select {
        box-sizing: border-box;
    }
    .todo-toast {
        background: var(--panel);
        border: 1px solid var(--border);
        border-left: 3px solid #f87171;
        border-radius: 8px;
        bottom: 20px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, .28);
        color: var(--text);
        display: none;
        font-size: 13px;
        max-width: min(360px, calc(100vw - 32px));
        padding: 12px 14px;
        position: fixed;
        right: 20px;
        z-index: 1001;
    }
    .todo-toast.visible { display: block; }

    @media (max-width: 980px) {
        .todo-shell { height: auto; min-height: calc(100vh - 46px); overflow: visible; }
        .pane-projects { min-width: 180px; width: 180px; }
        .pane-list { min-width: 260px; width: 320px; }
        .task-workspace-content { padding: 24px; }
    }

    @media (max-width: 720px) {
        .todo-shell { display: block; }
        .pane-projects { border-bottom: 1px solid var(--border); border-right: 0; min-height: auto; width: 100%; }
        .pane-projects > div:nth-child(2) { display: flex; overflow-x: auto; padding: 8px; }
        .pane-projects .project-link { flex: 0 0 auto; margin: 0 2px; }
        .pane-projects > div:nth-child(2) > div { display: none; }
        .pane-projects > div:last-child { display: none; }
        .pane-list { border-right: 0; height: 270px; width: 100%; }
        .pane-workspace { min-height: calc(100vh - 316px); }
        .task-workspace-content { padding: 20px 16px; }
        .task-workspace-header { flex-direction: column; }
        .task-workspace-header > div:last-child { width: 100%; }
        .task-workspace-header > div:last-child button { width: 100%; }
    }
</style>
@endsection

@section('content')
<!-- Global Nav Header -->
<header>
    <div class="brand">
        <a href="{{ route('workspace') }}" style="color:var(--text-muted);text-decoration:none;font-size:12px;margin-right:12px;display:inline-flex;align-items:center;gap:4px;">
            &larr; Chat Notes
        </a>
        <h1>Codebook</h1>
        <span style="background:var(--accent);color:#fff;font-size:10px;padding:1px 6px;border-radius:10px;margin-left:6px;">Tasks</span>
    </div>

    <div class="header-actions">
        <div class="user-badge">
            <span>&#128100; {{ Auth::user()->name }}</span>
        </div>
        <button class="icon-btn" id="theme-btn" title="Ganti Mode Tema"><span>Light mode</span></button>
        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="icon-btn" title="Keluar">&#x27A1; Logout</button>
        </form>
    </div>
</header>

<div class="todo-shell">
    <!-- 1. PROJECTS SIDEBAR -->
    <aside class="pane-projects">
        <div class="pane-header">
            <span>Projects</span>
            <button class="sidebar-tool-btn" onclick="openNewProjectModal()" title="New Project">+</button>
        </div>
        <div style="padding: 8px 0; overflow-y: auto; flex: 1;">
            <a href="{{ route('todos.index', ['status' => request('status')]) }}" class="project-link {{ !request('project_id') ? 'active' : '' }}">
                <span style="display:flex;align-items:center;">
                    <span class="project-dot" style="background:#94a3b8;"></span>
                    <span>All Tasks</span>
                </span>
                <span style="font-size:11px;color:var(--text-muted);">{{ $todos->count() }}</span>
            </a>

            <div style="font-size:10px;font-weight:600;color:var(--text-muted);padding:12px 14px 4px;text-transform:uppercase;letter-spacing:0.04em;">My Projects</div>
            @forelse($projects as $p)
                <a href="{{ route('todos.index', ['project_id' => $p->id, 'status' => request('status')]) }}" class="project-link {{ request('project_id') == $p->id ? 'active' : '' }}">
                    <span style="display:flex;align-items:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <span class="project-dot" style="background:{{ $p->color }};"></span>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $p->name }}</span>
                    </span>
                    <span class="project-pending-count" data-project-id="{{ $p->id }}" data-pending-count="{{ $p->pending_count }}" style="font-size:10px;background:var(--panel-2);padding:1px 6px;border-radius:10px;border:1px solid var(--border);{{ $p->pending_count > 0 ? '' : 'display:none;' }}">{{ $p->pending_count }}</span>
                </a>
            @empty
                <div style="font-size:12px;color:var(--text-muted);padding:10px 14px;">No projects yet</div>
            @endforelse
        </div>

        <!-- Filter Status Mini -->
        <div style="padding:10px; border-top:1px solid var(--border); font-size:12px;">
            <div style="font-size:10px;font-weight:600;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;">Status Filter</div>
            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => null])) }}" style="font-size:11px;padding:2px 8px;border-radius:4px;border:1px solid var(--border);text-decoration:none;color:{{ !request('status') ? 'var(--accent)' : 'var(--text-muted)' }};">All</a>
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => 'todo'])) }}" style="font-size:11px;padding:2px 8px;border-radius:4px;border:1px solid var(--border);text-decoration:none;color:{{ request('status') === 'todo' ? 'var(--accent)' : 'var(--text-muted)' }};">Todo</a>
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => 'in_progress'])) }}" style="font-size:11px;padding:2px 8px;border-radius:4px;border:1px solid var(--border);text-decoration:none;color:{{ request('status') === 'in_progress' ? 'var(--accent)' : 'var(--text-muted)' }};">In Progress</a>
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => 'blocked'])) }}" style="font-size:11px;padding:2px 8px;border-radius:4px;border:1px solid var(--border);text-decoration:none;color:{{ request('status') === 'blocked' ? 'var(--accent)' : 'var(--text-muted)' }};">Blocked</a>
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => 'done'])) }}" style="font-size:11px;padding:2px 8px;border-radius:4px;border:1px solid var(--border);text-decoration:none;color:{{ request('status') === 'done' ? 'var(--accent)' : 'var(--text-muted)' }};">Done</a>
            </div>
        </div>
    </aside>

    <!-- 2. TASK LIST (LINEAR STYLE) -->
    <section class="pane-list">
        <div class="pane-header">
            <span>Tasks</span>
            <button class="primary-btn" onclick="openNewTaskModal()" style="padding:3px 10px;font-size:12px;">+ New Task</button>
        </div>

        <div style="overflow-y:auto;flex:1;" id="task-list-container">
            @forelse($todos as $t)
                <div class="task-card {{ $t->status === 'done' ? 'done' : '' }} {{ $activeTodo && $activeTodo->id === $t->id ? 'active' : '' }}" id="task-card-{{ $t->id }}" data-project-id="{{ $t->todo_project_id }}">
                    <input type="checkbox" class="task-checkbox" {{ $t->status === 'done' ? 'checked' : '' }} 
                           onclick="toggleTaskDone({{ $t->id }}, this.checked)">
                    <a class="task-card-link" href="{{ route('todos.index', array_merge(request()->query(), ['active' => $t->id])) }}">
                    <div style="flex:1;min-width:0;">
                        <div class="task-title-text" style="font-size:13px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <span class="task-title-label">{{ $t->title }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                            @if($t->project)
                                <span style="font-size:10px;color:var(--text-muted);display:flex;align-items:center;gap:4px;">
                                    <span class="project-dot" style="width:6px;height:6px;background:{{ $t->project->color }};margin-right:0;"></span>
                                    {{ $t->project->name }}
                                </span>
                            @endif
                            <span class="badge badge-{{ $t->priority }}">{{ $t->priority }}</span>
                            @if($t->items->count() > 0)
                                <span style="font-size:10px;color:var(--text-muted);margin-left:auto;">&#128172; {{ $t->items->count() }}</span>
                            @endif
                        </div>
                    </div>
                    </a>
                </div>
            @empty
                <div style="padding:28px 16px;text-align:center;color:var(--text-muted);font-size:13px;">
                    No tasks found. Click <strong>+ New Task</strong> to create one!
                </div>
            @endforelse
        </div>
    </section>

    <!-- 3. TASK WORKSPACE (ACTIVE DETAILS, COMMANDS, THREAD) -->
    <main class="pane-workspace" id="task-workspace-container">
        @if($activeTodo)
            <!-- Workspace Content dynamically refreshed or server rendered -->
            <div class="task-workspace-content" style="max-width:860px; width:100%;">
                <!-- Header Info -->
                <div class="task-workspace-header">
                    <div style="flex:1;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                            <span class="badge badge-{{ $activeTodo->priority }}" id="ws-badge-priority">{{ $activeTodo->priority }}</span>
                            @if($activeTodo->project)
                                <span style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:6px;">
                                    <span class="project-dot" style="background:{{ $activeTodo->project->color }};"></span>
                                    {{ $activeTodo->project->name }}
                                </span>
                            @endif
                            <select onchange="updateTaskStatus({{ $activeTodo->id }}, this.value)" style="background:var(--panel-2);color:var(--text);border:1px solid var(--border);border-radius:4px;padding:2px 8px;font-size:12px;cursor:pointer;">
                                <option value="todo" {{ $activeTodo->status === 'todo' ? 'selected' : '' }}>Todo</option>
                                <option value="in_progress" {{ $activeTodo->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                <option value="blocked" {{ $activeTodo->status === 'blocked' ? 'selected' : '' }}>Blocked</option>
                                <option value="done" {{ $activeTodo->status === 'done' ? 'selected' : '' }}>Done</option>
                            </select>
                        </div>
                        <h2 style="font-size:20px;font-weight:600;margin:0 0 6px 0;" id="ws-task-title">{{ $activeTodo->title }}</h2>
                        <div style="font-size:13px;color:var(--text-muted);line-height:1.5;">
                            {{ $activeTodo->description ?: 'No initial description provided.' }}
                        </div>
                    </div>
                    <div>
                        <button class="icon-btn" onclick="deleteActiveTask({{ $activeTodo->id }})" title="Delete Task" style="color:#f87171;">&#128465; Delete</button>
                    </div>
                </div>

                <!-- SECTION: QUICK COMMANDS (Dev Perk) -->
                <div class="task-section">
                    <div class="task-section-heading">
                        <h3 style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted);margin:0;">
                            Quick Daily Commands
                        </h3>
                        <button class="icon-btn" onclick="openNewCommandModal({{ $activeTodo->id }})" style="font-size:11px;padding:2px 8px;">+ Add Command</button>
                    </div>

                    <div id="commands-container">
                        @php $commands = $activeTodo->items->where('type', 'command'); @endphp
                        @forelse($commands as $cmd)
                            <div class="cmd-box">
                                <span style="user-select:all;overflow-x:auto;">{{ $cmd->content }}</span>
                                <div style="display:flex;gap:6px;">
                                    <button class="cmd-copy-btn" onclick="copyToClipboard('{{ addslashes($cmd->content) }}', this)">Copy</button>
                                    <button class="cmd-copy-btn" onclick="deleteItem({{ $cmd->id }})" style="color:#f87171;">&times;</button>
                                </div>
                            </div>
                        @empty
                            <div style="font-size:12px;color:var(--text-muted);padding:8px 12px;background:var(--panel-2);border-radius:6px;border:1px dashed var(--border);">
                                Simpan shell command daily task ini (e.g., git branch, docker run, artisan) di sini untuk 1-click copy.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- SECTION: THREAD & NOTES / WORK LOG -->
                <div class="task-section">
                    <div class="task-section-heading">
                        <h3 style="font-size:12px;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;color:var(--text-muted);margin:0;">
                            Task Notes & Progress Thread
                        </h3>
                    </div>

                    <!-- New Note Form Input -->
                    <div style="background:var(--panel-2);border:1px solid var(--border);border-radius:6px;padding:12px;margin-bottom:16px;">
                        <textarea id="new-thread-input" placeholder="Tulis catatan, debug log, atau code snippet..."
                                  style="width:100%;background:var(--panel);border:1px solid var(--border);border-radius:4px;color:var(--text);padding:8px;font-size:13px;resize:vertical;min-height:70px;"></textarea>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;">
                            <span style="font-size:11px;color:var(--text-muted);">Plain text is saved securely.</span>
                            <button class="primary-btn" onclick="postThreadNote({{ $activeTodo->id }})" style="padding:4px 14px;font-size:12px;">Post Log</button>
                        </div>
                    </div>

                    <!-- Thread List -->
                    <div id="thread-stream-container">
                        @php $notes = $activeTodo->items->where('type', '!=', 'command'); @endphp
                        @forelse($notes as $note)
                            <div class="thread-entry" id="thread-entry-{{ $note->id }}">
                                <div style="display:flex;align-items:center;justify-content:space-between;">
                                    <div class="thread-time">{{ $note->created_at->format('d M Y, H:i') }} ({{ $note->type }})</div>
                                    <button onclick="deleteItem({{ $note->id }})" class="icon-btn" style="padding:0 4px;font-size:11px;color:#f87171;">&times;</button>
                                </div>
                                <div class="thread-markdown" style="font-size:13px;line-height:1.6;">
                                    {!! nl2br(e($note->content)) !!}
                                </div>
                            </div>
                        @empty
                            <div style="font-size:12px;color:var(--text-muted);text-align:center;padding:16px;">
                                Belum ada catatan thread. Tulis update harianmu di atas!
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        @else
            <div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:13px;">
                Pilih atau buat to-do baru untuk membuka workspace.
            </div>
        @endif
    </main>
</div>

<!-- Simple Modals -->
<!-- New Project Modal -->
<div id="modal-project" class="todo-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="project-modal-title">
    <div class="todo-modal" style="max-width:320px;">
        <h3 id="project-modal-title" style="font-size:15px;">New Project</h3>
        <input type="text" id="project-name-input" placeholder="Project Name (e.g. Codebook, API Client)" style="width:100%;margin-bottom:12px;background:var(--panel-2);border:1px solid var(--border);padding:8px;border-radius:4px;color:var(--text);">
        <div style="margin-bottom:16px;">
            <label style="font-size:12px;color:var(--text-muted);display:block;margin-bottom:4px;">Project Color:</label>
            <div style="display:flex;gap:8px;">
                <input type="color" id="project-color-input" value="#6366f1" style="height:32px;width:48px;border:none;background:none;cursor:pointer;">
            </div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button class="icon-btn" onclick="closeModals()">Cancel</button>
            <button class="primary-btn" onclick="submitNewProject()">Create</button>
        </div>
    </div>
</div>

<!-- New Task Modal -->
<div id="modal-task" class="todo-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="task-modal-title">
    <div class="todo-modal">
        <h3 id="task-modal-title" style="font-size:15px;">New Task</h3>
        <input type="text" id="task-title-input" placeholder="Task summary (e.g. Fix auth token refresh)" style="width:100%;margin-bottom:10px;background:var(--panel-2);border:1px solid var(--border);padding:8px;border-radius:4px;color:var(--text);">
        <textarea id="task-desc-input" placeholder="Description / notes (optional)" style="width:100%;margin-bottom:10px;background:var(--panel-2);border:1px solid var(--border);padding:8px;border-radius:4px;color:var(--text);font-size:12px;resize:vertical;"></textarea>
        
        <div style="display:flex;gap:8px;margin-bottom:16px;">
            <div style="flex:1;">
                <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Project:</label>
                <select id="task-project-select" style="width:100%;background:var(--panel-2);border:1px solid var(--border);padding:6px;border-radius:4px;color:var(--text);font-size:12px;">
                    <option value="">No Project</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="flex:1;">
                <label style="font-size:11px;color:var(--text-muted);display:block;margin-bottom:4px;">Priority:</label>
                <select id="task-priority-select" style="width:100%;background:var(--panel-2);border:1px solid var(--border);padding:6px;border-radius:4px;color:var(--text);font-size:12px;">
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button class="icon-btn" onclick="closeModals()">Cancel</button>
            <button class="primary-btn" onclick="submitNewTask()">Save Task</button>
        </div>
    </div>
</div>

<!-- New Command Modal -->
<div id="modal-command" class="todo-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="command-modal-title">
    <div class="todo-modal">
        <h3 id="command-modal-title" style="font-size:15px;">Add Quick Command</h3>
        <input type="text" id="command-text-input" placeholder="e.g. docker compose exec app php artisan migrate" style="width:100%;margin-bottom:14px;background:var(--panel-2);border:1px solid var(--border);padding:8px;border-radius:4px;color:var(--text);font-family:'JetBrains Mono',monospace;font-size:12px;">
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button class="icon-btn" onclick="closeModals()">Cancel</button>
            <button class="primary-btn" onclick="submitNewCommand()">Save Command</button>
        </div>
    </div>
</div>
<div id="todo-toast" class="todo-toast" role="alert" aria-live="assertive"></div>
@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let activeTodoId = {{ $activeTodo ? $activeTodo->id : 'null' }};
    const activeStatusFilter = @json($currentStatus);

    function showToast(message) {
        const toast = document.getElementById('todo-toast');
        toast.textContent = message;
        toast.classList.add('visible');
        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => toast.classList.remove('visible'), 4000);
    }

    async function todoRequest(url, options) {
        const response = await fetch(url, options);

        if (response.ok) {
            return response;
        }

        const payload = await response.json().catch(() => ({}));
        const message = payload.message || Object.values(payload.errors || {}).flat()[0] || 'Perubahan tidak dapat disimpan.';
        throw new Error(message);
    }

    function closeModals() {
        document.getElementById('modal-project').style.display = 'none';
        document.getElementById('modal-task').style.display = 'none';
        document.getElementById('modal-command').style.display = 'none';
    }

    function openNewProjectModal() {
        document.getElementById('modal-project').style.display = 'flex';
        document.getElementById('project-name-input').focus();
    }

    function openNewTaskModal() {
        document.getElementById('modal-task').style.display = 'flex';
        document.getElementById('task-title-input').focus();
    }

    function openNewCommandModal(todoId) {
        activeTodoId = todoId;
        document.getElementById('modal-command').style.display = 'flex';
        document.getElementById('command-text-input').focus();
    }

    async function submitNewProject() {
        const name = document.getElementById('project-name-input').value.trim();
        const color = document.getElementById('project-color-input').value;
        if (!name) {
            showToast('Nama project wajib diisi.');
            return;
        }

        try {
            await todoRequest("{{ route('todos.projects.store') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ name, color })
            });
            window.location.reload();
        } catch (error) {
            showToast(error.message);
        }
    }

    async function submitNewTask() {
        const title = document.getElementById('task-title-input').value.trim();
        const description = document.getElementById('task-desc-input').value.trim();
        const todo_project_id = document.getElementById('task-project-select').value || null;
        const priority = document.getElementById('task-priority-select').value;
        if (!title) {
            showToast('Judul task wajib diisi.');
            return;
        }

        try {
            const res = await todoRequest("{{ route('todos.store') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ title, description, todo_project_id, priority })
            });
            const data = await res.json();
            window.location.href = "{{ route('todos.index') }}?active=" + data.todo.id;
        } catch (error) {
            showToast(error.message);
        }
    }

    async function toggleTaskDone(todoId, isChecked) {
        const status = isChecked ? 'done' : 'todo';
        const checkbox = document.querySelector(`#task-card-${todoId} .task-checkbox`);
        const card = document.getElementById(`task-card-${todoId}`);
        const taskTitleLabel = card?.querySelector('.task-title-label');
        const wasDone = card?.classList.contains('done') ?? false;
        const projectId = card?.dataset.projectId;

        const setTaskCompletionState = (isDone) => {
            card?.classList.toggle('done', isDone);

            if (taskTitleLabel) {
                taskTitleLabel.style.textDecoration = isDone ? 'line-through' : 'none';
                taskTitleLabel.style.textDecorationThickness = isDone ? '2px' : '';
            }
        };

        setTaskCompletionState(isChecked);
        updateProjectPendingCount(projectId, wasDone, isChecked);

        try {
            const response = await todoRequest(`/todos/${todoId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ status })
            });
            const { todo } = await response.json();

            const serverIsDone = todo.status === 'done';

            if (serverIsDone !== isChecked) {
                setTaskCompletionState(serverIsDone);
                updateProjectPendingCount(projectId, isChecked, serverIsDone);
            }

            if (activeTodoId === todoId) {
                const statusSelect = document.querySelector('select[onchange^="updateTaskStatus"]');

                if (statusSelect) {
                    statusSelect.value = todo.status;
                }
            }

            if (activeStatusFilter && activeStatusFilter !== todo.status) {
                card?.remove();
            }
        } catch (error) {
            showToast(error.message);
            if (checkbox) {
                checkbox.checked = !isChecked;
            }
            setTaskCompletionState(wasDone);
            updateProjectPendingCount(projectId, isChecked, wasDone);
        }
    }

    function updateProjectPendingCount(projectId, wasDone, isDone) {
        if (!projectId || wasDone === isDone) {
            return;
        }

        const badge = document.querySelector(`.project-pending-count[data-project-id="${projectId}"]`);

        if (!badge) {
            return;
        }

        const currentCount = Number(badge.dataset.pendingCount || 0);
        const nextCount = Math.max(0, currentCount + (isDone ? -1 : 1));

        badge.dataset.pendingCount = String(nextCount);
        badge.textContent = String(nextCount);
        badge.style.display = nextCount > 0 ? 'inline-block' : 'none';
    }

    async function updateTaskStatus(todoId, status) {
        try {
            await todoRequest(`/todos/${todoId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ status })
            });
            window.location.reload();
        } catch (error) {
            showToast(error.message);
        }
    }

    async function deleteActiveTask(todoId) {
        if (!confirm('Yakin ingin menghapus task ini?')) return;
        try {
            await todoRequest(`/todos/${todoId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            window.location.href = "{{ route('todos.index') }}";
        } catch (error) {
            showToast(error.message);
        }
    }

    async function submitNewCommand() {
        const content = document.getElementById('command-text-input').value.trim();
        if (!content || !activeTodoId) {
            showToast('Command wajib diisi.');
            return;
        }

        try {
            await todoRequest(`/todos/${activeTodoId}/items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ type: 'command', content })
            });
            window.location.reload();
        } catch (error) {
            showToast(error.message);
        }
    }

    async function postThreadNote(todoId) {
        const input = document.getElementById('new-thread-input');
        const content = input.value.trim();
        if (!content) {
            showToast('Catatan wajib diisi.');
            return;
        }

        try {
            await todoRequest(`/todos/${todoId}/items`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ type: 'note', content })
            });
            window.location.reload();
        } catch (error) {
            showToast(error.message);
        }
    }

    async function deleteItem(itemId) {
        if (!confirm('Hapus item ini?')) return;
        try {
            await todoRequest(`/todos/items/${itemId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            window.location.reload();
        } catch (error) {
            showToast(error.message);
        }
    }

    function copyToClipboard(text, btnElement) {
        navigator.clipboard.writeText(text).then(() => {
            const originalText = btnElement.innerText;
            btnElement.innerText = 'Copied!';
            btnElement.style.borderColor = '#10b981';
            btnElement.style.color = '#10b981';
            setTimeout(() => {
                btnElement.innerText = originalText;
                btnElement.style.borderColor = '';
                btnElement.style.color = '';
            }, 1500);
        }).catch(() => showToast('Clipboard tidak tersedia.'));
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeModals();
        }
    });

    document.querySelectorAll('.todo-modal-overlay').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModals();
            }
        });
    });

    // Theme toggle
    document.getElementById('theme-btn')?.addEventListener('click', () => {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('codebook_theme', newTheme);
        const btnText = document.querySelector('#theme-btn span');
        if (btnText) btnText.textContent = newTheme === 'dark' ? 'Light mode' : 'Dark mode';
    });
</script>
@endsection

