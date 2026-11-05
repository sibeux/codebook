@extends('layouts.app')

@section('title', 'Tasks & Projects')

@section('styles')
<style>
/* =========================================================
   TODOS PAGE — Modern Linear-Inspired 3-Column Shell
   ========================================================= */

/* ---- CSS Custom Properties (Todo-Scoped) ---- */
:root {
    --todo-accent: #6366f1;
    --todo-accent-soft: rgba(99,102,241,0.12);
    --todo-accent-muted: rgba(99,102,241,0.25);
    --todo-radius-sm: 6px;
    --todo-radius-md: 10px;
    --todo-radius-lg: 14px;
    --todo-transition: 0.14s cubic-bezier(.4,0,.2,1);
}

/* ---- Shell Layout ---- */
body:has(.todo-shell) {
    background: var(--bg);
    overflow: hidden;
}

body:has(.todo-shell) > header,
body:has(.todo-shell) header {
    height: 52px;
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--panel);
    border-bottom: 1px solid var(--border);
    box-shadow: 0 1px 0 rgba(0,0,0,.04);
    position: relative;
    z-index: 10;
    flex-shrink: 0;
}

body:has(.todo-shell) .brand { gap: 8px; align-items: center; }
body:has(.todo-shell) .brand h1 { font-size: 15px; letter-spacing: -.02em; }
body:has(.todo-shell) .header-actions { gap: 8px; }
body:has(.todo-shell) .user-badge { padding: 5px 10px; border-radius: 999px; background: var(--panel-2); border: 1px solid var(--border); }
body:has(.todo-shell) .icon-btn,
body:has(.todo-shell) .primary-btn { border-radius: var(--todo-radius-sm); height: 32px; padding: 0 12px; font-size: 12px; }

.todo-shell {
    display: flex;
    height: calc(100vh - 52px);
    overflow: hidden;
    background: var(--bg);
}

/* ---- Panes ---- */
.pane-projects,
.pane-list,
.pane-workspace {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

/* Pane 1: Projects */
.pane-projects {
    width: 240px;
    min-width: 200px;
    flex-shrink: 0;
    background: var(--sidebar-bg);
    border-right: 1px solid var(--border);
}

/* Pane 2: Task List */
.pane-list {
    width: 360px;
    min-width: 300px;
    flex-shrink: 0;
    background: var(--panel);
    border-right: 1px solid var(--border);
}

/* Pane 3: Workspace */
.pane-workspace {
    flex: 1;
    background: var(--bg);
    overflow-y: auto;
}

/* ---- Pane Headers ---- */
.pane-header {
    height: 52px;
    padding: 0 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
    background: inherit;
}

.pane-header-title {
    font-size: 12.5px;
    font-weight: 600;
    color: var(--text);
    letter-spacing: -.01em;
    display: flex;
    align-items: center;
    gap: 6px;
}

.pane-header-subtitle {
    font-size: 11px;
    color: var(--muted);
    font-weight: 400;
}

/* ---- Project List ---- */
.project-list-scroll {
    flex: 1;
    overflow-y: auto;
    padding: 8px 6px;
}

.project-section-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--muted);
    padding: 10px 10px 4px;
}

.project-link {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 7px 10px;
    border-radius: var(--todo-radius-sm);
    font-size: 12.5px;
    color: var(--text-secondary);
    text-decoration: none;
    cursor: pointer;
    transition: background var(--todo-transition), color var(--todo-transition);
    gap: 8px;
    border: 1px solid transparent;
    margin: 1px 0;
}

.project-link:hover {
    background: var(--panel-hover);
    color: var(--text);
}

.project-link.active {
    background: var(--todo-accent-soft);
    color: var(--text);
    font-weight: 500;
    border-color: var(--todo-accent-muted);
}

.project-link-left {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}

.project-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    flex-shrink: 0;
}

.project-link-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.project-count-badge {
    font-size: 10px;
    font-weight: 500;
    padding: 1px 6px;
    border-radius: 999px;
    background: var(--panel-2);
    border: 1px solid var(--border);
    color: var(--muted);
    flex-shrink: 0;
    font-variant-numeric: tabular-nums;
}

/* ---- Status Filter Chips ---- */
.status-filter-section {
    padding: 10px 10px 12px;
    border-top: 1px solid var(--border);
    flex-shrink: 0;
}

.status-filter-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--muted);
    margin-bottom: 7px;
}

.status-filter-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

.status-chip {
    font-size: 11px;
    padding: 3px 9px;
    border-radius: 999px;
    border: 1px solid var(--border);
    text-decoration: none;
    color: var(--muted);
    background: transparent;
    transition: all var(--todo-transition);
    font-weight: 500;
}

.status-chip:hover { background: var(--panel-hover); color: var(--text); border-color: var(--border-hover); }
.status-chip.active { background: var(--todo-accent-soft); color: var(--todo-accent); border-color: var(--todo-accent-muted); }

/* ---- Task Cards ---- */
.task-list-scroll {
    flex: 1;
    overflow-y: auto;
}

.task-card {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 11px 16px;
    border-bottom: 1px solid color-mix(in srgb, var(--border) 70%, transparent);
    cursor: pointer;
    transition: background var(--todo-transition);
    position: relative;
    min-height: 64px;
}

.task-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 2.5px;
    height: 0;
    background: var(--todo-accent);
    border-radius: 0 2px 2px 0;
    transition: height var(--todo-transition);
}

.task-card.active::before,
.task-card:hover::before { height: 28px; }
.task-card.active::before { height: 32px; }

.task-card:hover { background: color-mix(in srgb, var(--todo-accent) 4%, var(--panel)); }
.task-card.active { background: color-mix(in srgb, var(--todo-accent) 7%, var(--panel)); }

/* Custom Checkbox */
.task-checkbox-wrap {
    flex-shrink: 0;
    margin-top: 2px;
}

.task-checkbox {
    appearance: none;
    -webkit-appearance: none;
    width: 16px;
    height: 16px;
    border: 1.5px solid var(--border-hover);
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--todo-transition);
    background: transparent;
    flex-shrink: 0;
    position: relative;
}

.task-checkbox::after {
    content: '';
    width: 5px;
    height: 8px;
    border: 2px solid #fff;
    border-top: none;
    border-left: none;
    transform: rotate(45deg) scale(0);
    transition: transform var(--todo-transition);
    position: absolute;
    top: 1px;
}

.task-checkbox:hover {
    border-color: var(--todo-accent);
    background: var(--todo-accent-soft);
}

.task-checkbox:checked {
    background: var(--todo-accent);
    border-color: var(--todo-accent);
}

.task-checkbox:checked::after { transform: rotate(45deg) scale(1); }

.task-card.done .task-checkbox { background: var(--todo-accent); border-color: var(--todo-accent); opacity: .7; }

.task-card-body { flex: 1; min-width: 0; }

.task-card-link {
    color: inherit;
    text-decoration: none;
    display: block;
}

.task-title-text {
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--text);
    transition: color var(--todo-transition);
    line-height: 1.4;
}

.task-card.done .task-title-text { color: var(--muted); }

.task-title-label {
    display: inline;
    position: relative;
}

.task-title-label::after {
    background: currentColor;
    content: '';
    height: 1.5px;
    left: 0;
    pointer-events: none;
    position: absolute;
    top: 52%;
    transform: scaleX(0);
    transform-origin: left center;
    transition: transform .22s cubic-bezier(.22,1,.36,1);
    width: 100%;
}

.task-card.done .task-title-label::after { transform: scaleX(1); }

.task-card-meta {
    display: flex;
    align-items: center;
    gap: 5px;
    margin-top: 4px;
    flex-wrap: wrap;
}

/* ---- Badges ---- */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 999px;
    text-transform: capitalize;
    letter-spacing: .02em;
    flex-shrink: 0;
}

.badge-urgent  { background: rgba(239,68,68,.12);  color: #f87171; border: 1px solid rgba(239,68,68,.22); }
.badge-high    { background: rgba(249,115,22,.12); color: #fb923c; border: 1px solid rgba(249,115,22,.22); }
.badge-medium  { background: rgba(99,102,241,.12); color: #818cf8; border: 1px solid rgba(99,102,241,.22); }
.badge-low     { background: rgba(107,114,128,.1); color: #9ca3af; border: 1px solid rgba(107,114,128,.2); }

.task-meta-sep { width: 3px; height: 3px; border-radius: 50%; background: var(--border-hover); flex-shrink: 0; }

.task-items-count {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 10px;
    color: var(--muted);
}

/* ---- Workspace Pane ---- */
.task-workspace-content {
    padding: 32px clamp(20px, 4vw, 56px);
    max-width: 800px;
    width: 100%;
}

.ws-empty-state {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    color: var(--muted);
    padding: 48px 24px;
    text-align: center;
}

.ws-empty-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--panel-2);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted);
    margin-bottom: 4px;
}

.ws-empty-state p { font-size: 13px; margin: 0; }
.ws-empty-state span { font-size: 12px; color: var(--muted); opacity: .7; }

/* Workspace Header Card */
.ws-header-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: var(--todo-radius-lg);
    padding: 24px 28px;
    margin-bottom: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}

.ws-header-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}

.ws-header-badges {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 10px;
    flex-wrap: wrap;
}

.ws-task-title {
    font-size: clamp(18px, 2.5vw, 24px);
    font-weight: 700;
    color: var(--text);
    margin: 0 0 4px;
    letter-spacing: -.03em;
    line-height: 1.25;
}

.ws-task-desc {
    font-size: 13.5px;
    color: var(--text-secondary);
    line-height: 1.6;
    margin-top: 6px;
    margin-bottom: 0;
}

/* Status Select — modern pill */
.ws-status-select {
    appearance: none;
    -webkit-appearance: none;
    background: var(--panel-2);
    border: 1px solid var(--border);
    border-radius: 999px;
    color: var(--text);
    font-size: 11.5px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    padding: 4px 24px 4px 10px;
    cursor: pointer;
    outline: none;
    transition: all var(--todo-transition);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 7px center;
}

.ws-status-select:hover { border-color: var(--todo-accent); }
.ws-status-select:focus { border-color: var(--todo-accent); box-shadow: 0 0 0 3px var(--todo-accent-soft); }

/* Title edit inline */
.ws-title-edit-wrap { display: none; margin-bottom: 8px; }

.ws-title-input {
    width: 100%;
    background: var(--panel-2);
    border: 1.5px solid var(--todo-accent);
    border-radius: var(--todo-radius-sm);
    color: var(--text);
    font-family: 'Space Grotesk', sans-serif;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -.02em;
    padding: 8px 12px;
    outline: none;
    box-shadow: 0 0 0 3px var(--todo-accent-soft);
    box-sizing: border-box;
}

.ws-title-edit-actions {
    display: flex;
    gap: 6px;
    margin-top: 8px;
}

/* Delete button */
.ws-delete-btn {
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--todo-radius-sm);
    color: var(--muted);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-family: 'Inter', sans-serif;
    padding: 5px 10px;
    transition: all var(--todo-transition);
    flex-shrink: 0;
}

.ws-delete-btn:hover {
    background: rgba(239,68,68,.1);
    color: #f87171;
    border-color: rgba(239,68,68,.2);
}

/* Edit title btn */
.ws-edit-title-btn {
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--todo-radius-sm);
    color: var(--muted);
    cursor: pointer;
    font-size: 11px;
    font-family: 'Inter', sans-serif;
    padding: 2px 7px;
    transition: all var(--todo-transition);
    flex-shrink: 0;
}

.ws-edit-title-btn:hover {
    background: var(--panel-2);
    border-color: var(--border);
    color: var(--text);
}

/* ---- Sections ---- */
.ws-section {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: var(--todo-radius-md);
    padding: 18px 20px;
    margin-bottom: 14px;
}

.ws-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
}

.ws-section-title {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ---- Command Boxes ---- */
.cmd-box {
    background: var(--panel-2);
    border: 1px solid color-mix(in srgb, var(--todo-accent) 20%, var(--border));
    border-radius: var(--todo-radius-sm);
    padding: 10px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 7px;
    font-family: 'JetBrains Mono', monospace;
    font-size: 12px;
    color: var(--text);
    transition: border-color var(--todo-transition);
}

.cmd-box:hover { border-color: color-mix(in srgb, var(--todo-accent) 45%, var(--border)); }

.cmd-box-code {
    user-select: all;
    overflow-x: auto;
    white-space: nowrap;
    flex: 1;
    color: var(--text-secondary);
    font-family: 'JetBrains Mono', monospace;
}

.cmd-box-actions { display: flex; gap: 5px; flex-shrink: 0; }

.cmd-btn {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 5px;
    color: var(--muted);
    cursor: pointer;
    font-size: 11px;
    font-family: 'Inter', sans-serif;
    padding: 3px 9px;
    transition: all var(--todo-transition);
}

.cmd-btn:hover { color: var(--text); border-color: var(--border-hover); background: var(--panel-hover); }
.cmd-btn.danger:hover { color: #f87171; border-color: rgba(239,68,68,.3); background: rgba(239,68,68,.08); }

.cmd-empty {
    font-size: 12px;
    color: var(--muted);
    padding: 10px 12px;
    background: var(--panel-2);
    border: 1px dashed var(--border);
    border-radius: var(--todo-radius-sm);
    line-height: 1.6;
}

/* ---- Thread / Notes ---- */
.thread-input-wrap {
    background: var(--panel-2);
    border: 1px solid var(--border);
    border-radius: var(--todo-radius-sm);
    padding: 12px;
    margin-bottom: 14px;
    transition: border-color var(--todo-transition), box-shadow var(--todo-transition);
}

.thread-input-wrap:focus-within {
    border-color: var(--todo-accent);
    box-shadow: 0 0 0 3px var(--todo-accent-soft);
}

.thread-textarea {
    width: 100%;
    background: transparent;
    border: none;
    color: var(--text);
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    line-height: 1.6;
    padding: 0;
    resize: vertical;
    min-height: 68px;
    outline: none;
    box-sizing: border-box;
}

.thread-textarea::placeholder { color: var(--muted); }

.thread-input-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid var(--border);
}

.thread-input-hint { font-size: 11px; color: var(--muted); }

.thread-entry {
    background: var(--panel-2);
    border: 1px solid var(--border);
    border-left: 2.5px solid color-mix(in srgb, var(--todo-accent) 55%, var(--border));
    border-radius: 0 var(--todo-radius-sm) var(--todo-radius-sm) 0;
    padding: 11px 14px;
    margin-bottom: 8px;
    transition: border-color var(--todo-transition);
}

.thread-entry:hover { border-left-color: var(--todo-accent); }

.thread-entry-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
}

.thread-entry-time {
    font-family: 'JetBrains Mono', monospace;
    font-size: 10.5px;
    color: var(--muted);
    display: flex;
    align-items: center;
    gap: 6px;
}

.thread-type-dot {
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 999px;
    text-transform: capitalize;
    background: var(--todo-accent-soft);
    color: var(--todo-accent);
    border: 1px solid var(--todo-accent-muted);
    font-family: 'Inter', sans-serif;
}

.thread-delete-btn {
    background: transparent;
    border: none;
    color: var(--muted);
    cursor: pointer;
    font-size: 15px;
    line-height: 1;
    padding: 0 3px;
    border-radius: 3px;
    transition: color var(--todo-transition);
}

.thread-delete-btn:hover { color: #f87171; }

.thread-content {
    font-size: 13px;
    color: var(--text-secondary);
    line-height: 1.65;
    white-space: pre-wrap;
    word-break: break-word;
    margin: 0;
}

.thread-empty {
    font-size: 12.5px;
    color: var(--muted);
    text-align: center;
    padding: 20px;
}

/* ---- Modals ---- */
.todo-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(4,8,20,.72);
    backdrop-filter: blur(6px);
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    z-index: 999;
}

.todo-modal {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: var(--todo-radius-lg);
    box-shadow: 0 20px 60px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.04);
    max-width: 420px;
    width: 100%;
    padding: 24px;
    animation: modalIn .15s cubic-bezier(.4,0,.2,1);
}

@keyframes modalIn {
    from { opacity: 0; transform: scale(.96) translateY(4px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.todo-modal-title {
    font-size: 15px;
    font-weight: 700;
    color: var(--text);
    margin: 0 0 18px;
    letter-spacing: -.01em;
}

.form-group { margin-bottom: 14px; }

.form-label {
    display: block;
    font-size: 10.5px;
    font-weight: 700;
    color: var(--muted);
    margin-bottom: 5px;
    text-transform: uppercase;
    letter-spacing: .06em;
}

.form-input,
.form-textarea,
.form-select {
    width: 100%;
    box-sizing: border-box;
    background: var(--panel-2);
    border: 1px solid var(--border);
    border-radius: var(--todo-radius-sm);
    color: var(--text);
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    padding: 8px 11px;
    outline: none;
    transition: all var(--todo-transition);
}

.form-input::placeholder,
.form-textarea::placeholder { color: var(--muted); }

.form-input:focus,
.form-textarea:focus,
.form-select:focus {
    border-color: var(--todo-accent);
    box-shadow: 0 0 0 3px var(--todo-accent-soft);
}

.form-textarea { resize: vertical; min-height: 72px; }
.form-select { cursor: pointer; }

.modal-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }

/* ---- Primary & Icon Buttons in Todos ---- */
.primary-btn {
    background: var(--todo-accent);
    border: 1px solid transparent;
    border-radius: var(--todo-radius-sm);
    color: #fff;
    cursor: pointer;
    font-family: 'Inter', sans-serif;
    font-size: 12.5px;
    font-weight: 600;
    padding: 6px 14px;
    transition: all var(--todo-transition);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    white-space: nowrap;
}

.primary-btn:hover { filter: brightness(1.1); box-shadow: 0 3px 10px rgba(99,102,241,.35); }
.primary-btn:active { transform: translateY(1px); box-shadow: none; }

/* ---- Add button for sidebar ---- */
.pane-add-btn {
    background: transparent;
    border: 1px solid transparent;
    border-radius: var(--todo-radius-sm);
    color: var(--muted);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    font-size: 18px;
    line-height: 1;
    transition: all var(--todo-transition);
}

.pane-add-btn:hover { background: var(--panel-2); color: var(--text); border-color: var(--border); }

/* ---- Toast Notification ---- */
.todo-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: var(--panel);
    border: 1px solid var(--border);
    border-left: 3px solid #f87171;
    border-radius: var(--todo-radius-md);
    box-shadow: 0 8px 24px rgba(0,0,0,.24);
    color: var(--text);
    display: none;
    font-size: 13px;
    max-width: 340px;
    padding: 12px 16px;
    z-index: 1001;
    animation: toastIn .2s cubic-bezier(.4,0,.2,1);
}

@keyframes toastIn { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform:none; } }
.todo-toast.visible { display: block; }

/* ---- Loading Bar ---- */
.todo-loading-bar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 2.5px;
    z-index: 2000;
    background: transparent;
    opacity: 0;
    visibility: hidden;
    transition: opacity .16s ease;
}

.todo-loading-bar::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, var(--todo-accent) 0%, #06b6d4 100%);
    box-shadow: 0 0 10px rgba(99,102,241,.8);
    transform: scaleX(var(--todo-load-progress, 0));
    transform-origin: left center;
    transition: transform .18s ease-out;
}

.todo-loading-bar.visible { opacity: 1; visibility: visible; }

/* ---- Responsive ---- */
@media (max-width: 1100px) {
    .pane-projects { width: 200px; min-width: 160px; }
    .pane-list { width: 320px; min-width: 260px; }
}

@media (max-width: 860px) {
    .todo-shell { display: block; height: auto; min-height: calc(100vh - 52px); overflow: visible; }
    body:has(.todo-shell) { overflow: auto; }
    .pane-projects { width: 100%; border-right: none; border-bottom: 1px solid var(--border); }
    .project-list-scroll { display: flex; flex-direction: row; overflow-x: auto; overflow-y: hidden; padding: 6px 8px; gap: 4px; max-height: 48px; }
    .project-section-label, .status-filter-section { display: none; }
    .project-link { white-space: nowrap; flex-shrink: 0; }
    .pane-list { width: 100%; border-right: none; height: min(360px, 44vh); }
    .pane-workspace { min-height: calc(100vh - 500px); }
    .task-workspace-content { padding: 20px 16px; }
    .ws-header-card { padding: 18px 16px; }
}
</style>
@endsection

@section('content')
{{-- Loading Bar --}}
<div id="todo-loading-bar" class="todo-loading-bar visible" role="progressbar" aria-label="Loading" aria-valuemin="0" aria-valuemax="100" aria-valuenow="8" style="--todo-load-progress:.08"></div>

{{-- ===== HEADER ===== --}}
<header>
    <div class="brand">
        <a href="{{ route('workspace') }}" style="color:var(--muted);text-decoration:none;font-size:12px;display:inline-flex;align-items:center;gap:4px;padding-right:12px;border-right:1px solid var(--border);">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            Notes
        </a>
        <h1>Codebook</h1>
        <span style="background:var(--todo-accent-soft);color:var(--todo-accent);border:1px solid var(--todo-accent-muted);font-family:'Inter',sans-serif;font-weight:600;font-size:10.5px;padding:2px 8px;border-radius:999px;">Tasks</span>
    </div>

    <div class="header-actions">
        <div class="user-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>{{ Auth::user()->name }}</span>
        </div>
        <button class="icon-btn" id="theme-btn" title="Toggle Theme">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            <span>Theme</span>
        </button>
        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="icon-btn">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </button>
        </form>
    </div>
</header>

{{-- ===== 3-COLUMN SHELL ===== --}}
<div class="todo-shell" data-active-todo-id="{{ $activeTodo?->id ?? '' }}" data-project-id="{{ $currentProjectId ?? '' }}" data-status-filter="{{ $currentStatus ?? '' }}">

    {{-- ===== PANE 1: PROJECTS ===== --}}
    <aside class="pane-projects">
        <div class="pane-header">
            <span class="pane-header-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Projects
            </span>
            <button class="pane-add-btn" onclick="openNewProjectModal()" title="New Project">+</button>
        </div>

        <div class="project-list-scroll">
            <a href="{{ route('todos.index', ['status' => request('status')]) }}" onclick="return navigateTodo(event, this)" class="project-link {{ !$currentProject ? 'active' : '' }}">
                <span class="project-link-left">
                    <span class="project-dot" style="background:#94a3b8;"></span>
                    <span class="project-link-name">All Tasks</span>
                </span>
                <span class="project-count-badge">{{ $todos->count() }}</span>
            </a>

            <div class="project-section-label">My Projects</div>

            @forelse($projects as $p)
                <a href="{{ route('todos.index', ['project_id' => $p->id, 'status' => request('status')]) }}" onclick="return navigateTodo(event, this)" class="project-link {{ $currentProject?->id === $p->id ? 'active' : '' }}">
                    <span class="project-link-left">
                        <span class="project-dot" style="background:{{ $p->color }};"></span>
                        <span class="project-link-name">{{ $p->name }}</span>
                    </span>
                    <span class="project-count-badge project-pending-count" data-project-id="{{ $p->id }}" data-pending-count="{{ $p->pending_count }}" style="{{ $p->pending_count > 0 ? '' : 'display:none;' }}">{{ $p->pending_count }}</span>
                </a>
            @empty
                <div style="font-size:12px;color:var(--muted);padding:10px 10px;line-height:1.6;">
                    No projects yet.<br>
                    <span style="opacity:.7">Click <strong>+</strong> to create one.</span>
                </div>
            @endforelse
        </div>

        <div class="status-filter-section">
            <div class="status-filter-label">Filter by Status</div>
            <div class="status-filter-chips">
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => null])) }}" onclick="return navigateTodo(event, this)" class="status-chip {{ !request('status') ? 'active' : '' }}">All</a>
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => 'todo'])) }}" onclick="return navigateTodo(event, this)" class="status-chip {{ request('status') === 'todo' ? 'active' : '' }}">Todo</a>
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => 'in_progress'])) }}" onclick="return navigateTodo(event, this)" class="status-chip {{ request('status') === 'in_progress' ? 'active' : '' }}">In Progress</a>
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => 'blocked'])) }}" onclick="return navigateTodo(event, this)" class="status-chip {{ request('status') === 'blocked' ? 'active' : '' }}">Blocked</a>
                <a href="{{ route('todos.index', array_merge(request()->query(), ['status' => 'done'])) }}" onclick="return navigateTodo(event, this)" class="status-chip {{ request('status') === 'done' ? 'active' : '' }}">Done</a>
            </div>
        </div>
    </aside>

    {{-- ===== PANE 2: TASK LIST ===== --}}
    <section class="pane-list">
        <div class="pane-header">
            <span class="pane-header-title">
                Tasks
                @if($currentProject)
                    <span class="pane-header-subtitle">/ {{ $currentProject->name }}</span>
                @endif
            </span>
            <button class="primary-btn" onclick="openNewTaskModal()" style="height:28px;padding:0 10px;font-size:11.5px;">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                New Task
            </button>
        </div>

        <div class="task-list-scroll" id="task-list-container">
            @forelse($todos as $t)
                <div class="task-card {{ $t->status === 'done' ? 'done' : '' }} {{ $activeTodo && $activeTodo->id === $t->id ? 'active' : '' }}" id="task-card-{{ $t->id }}" data-project-id="{{ $t->todo_project_id }}">
                    <div class="task-checkbox-wrap">
                        <input type="checkbox" class="task-checkbox" {{ $t->status === 'done' ? 'checked' : '' }}
                               onclick="toggleTaskDone({{ $t->id }}, this.checked)">
                    </div>
                    <div class="task-card-body">
                        <a class="task-card-link" href="{{ route('todos.index', array_merge(request()->query(), ['active' => $t->id])) }}" onclick="return navigateTodo(event, this)">
                            <div class="task-title-text">
                                <span class="task-title-label">{{ $t->title }}</span>
                            </div>
                            <div class="task-card-meta">
                                @if($t->project)
                                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:10.5px;color:var(--muted);">
                                        <span class="project-dot" style="width:5px;height:5px;background:{{ $t->project->color }};"></span>
                                        {{ $t->project->name }}
                                    </span>
                                    <span class="task-meta-sep"></span>
                                @endif
                                <span class="badge badge-{{ $t->priority }}">{{ $t->priority }}</span>
                                @if($t->items->count() > 0)
                                    <span class="task-meta-sep"></span>
                                    <span class="task-items-count">
                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                        {{ $t->items->count() }}
                                    </span>
                                @endif
                            </div>
                        </a>
                    </div>
                </div>
            @empty
                <div style="padding:40px 24px;text-align:center;">
                    <div style="width:36px;height:36px;border-radius:10px;background:var(--panel-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </div>
                    <div style="font-size:13px;color:var(--text-secondary);font-weight:500;">No tasks yet</div>
                    <div style="font-size:11.5px;color:var(--muted);margin-top:3px;">Click <strong style="color:var(--text);">+ New Task</strong> to get started</div>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ===== PANE 3: WORKSPACE ===== --}}
    <main class="pane-workspace" id="task-workspace-container">
        @if($activeTodo)
            <div class="task-workspace-content">

                {{-- Header Card --}}
                <div class="ws-header-card">
                    <div class="ws-header-top">
                        <div style="flex:1;min-width:0;">
                            <div class="ws-header-badges">
                                <span class="badge badge-{{ $activeTodo->priority }}" id="ws-badge-priority">{{ $activeTodo->priority }}</span>
                                @if($activeTodo->project)
                                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:11.5px;color:var(--muted);">
                                        <span class="project-dot" style="background:{{ $activeTodo->project->color }};"></span>
                                        {{ $activeTodo->project->name }}
                                    </span>
                                @endif
                                <select class="ws-status-select" onchange="updateTaskStatus({{ $activeTodo->id }}, this.value)">
                                    <option value="todo"        {{ $activeTodo->status === 'todo'        ? 'selected' : '' }}>⬜ Todo</option>
                                    <option value="in_progress" {{ $activeTodo->status === 'in_progress' ? 'selected' : '' }}>🔄 In Progress</option>
                                    <option value="blocked"     {{ $activeTodo->status === 'blocked'     ? 'selected' : '' }}>🚫 Blocked</option>
                                    <option value="done"        {{ $activeTodo->status === 'done'        ? 'selected' : '' }}>✅ Done</option>
                                </select>
                            </div>

                            {{-- Title display --}}
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;">
                                <h2 class="ws-task-title" id="ws-task-title">{{ $activeTodo->title }}</h2>
                                <button type="button" class="ws-edit-title-btn" id="edit-task-title-btn" onclick="startTaskTitleEdit()">Edit</button>
                            </div>

                            {{-- Title editor --}}
                            <div class="ws-title-edit-wrap" id="ws-task-title-editor">
                                <input type="text" id="ws-task-title-input" class="ws-title-input" value="{{ $activeTodo->title }}" maxlength="255" aria-label="Task title">
                                <div class="ws-title-edit-actions">
                                    <button type="button" class="primary-btn" onclick="saveTaskTitle()" style="font-size:11.5px;height:28px;padding:0 12px;">Save</button>
                                    <button type="button" class="icon-btn" onclick="cancelTaskTitleEdit()" style="font-size:11.5px;height:28px;">Cancel</button>
                                </div>
                            </div>

                            <p class="ws-task-desc">{{ $activeTodo->description ?: 'No description provided.' }}</p>
                        </div>

                        <button class="ws-delete-btn" onclick="deleteActiveTask({{ $activeTodo->id }})" title="Delete Task">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                            Delete
                        </button>
                    </div>
                </div>

                {{-- Quick Commands --}}
                <div class="ws-section">
                    <div class="ws-section-header">
                        <span class="ws-section-title">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>
                            Quick Commands
                        </span>
                        <button class="icon-btn" onclick="openNewCommandModal({{ $activeTodo->id }})" style="height:26px;padding:0 10px;font-size:11px;">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add
                        </button>
                    </div>

                    <div id="commands-container">
                        @php $commands = $activeTodo->items->where('type', 'command'); @endphp
                        @forelse($commands as $cmd)
                            <div class="cmd-box">
                                <code class="cmd-box-code">{{ $cmd->content }}</code>
                                <div class="cmd-box-actions">
                                    <button class="cmd-btn" onclick="copyToClipboard('{{ addslashes($cmd->content) }}', this)">Copy</button>
                                    <button class="cmd-btn danger" onclick="deleteItem({{ $cmd->id }})">&times;</button>
                                </div>
                            </div>
                        @empty
                            <div class="cmd-empty">
                                Save daily shell commands here (e.g. <code style="font-family:'JetBrains Mono',monospace;font-size:11px;background:var(--panel);padding:1px 4px;border-radius:3px;">git branch</code>, <code style="font-family:'JetBrains Mono',monospace;font-size:11px;background:var(--panel);padding:1px 4px;border-radius:3px;">php artisan migrate</code>) for one-click copy.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Notes & Thread --}}
                <div class="ws-section">
                    <div class="ws-section-header">
                        <span class="ws-section-title">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Notes & Progress Thread
                        </span>
                    </div>

                    <div class="thread-input-wrap">
                        <textarea id="new-thread-input" class="thread-textarea" placeholder="Write a note, debug log, or code snippet..."></textarea>
                        <div class="thread-input-footer">
                            <span class="thread-input-hint">Stored securely &mdash; plain text.</span>
                            <button class="primary-btn" onclick="postThreadNote({{ $activeTodo->id }})" style="height:28px;padding:0 12px;font-size:11.5px;">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                                Post
                            </button>
                        </div>
                    </div>

                    <div id="thread-stream-container">
                        @php $notes = $activeTodo->items->where('type', '!=', 'command'); @endphp
                        @forelse($notes as $note)
                            <div class="thread-entry" id="thread-entry-{{ $note->id }}">
                                <div class="thread-entry-header">
                                    <span class="thread-entry-time">
                                        <span class="thread-type-dot">{{ $note->type }}</span>
                                        {{ $note->created_at->format('d M Y, H:i') }}
                                    </span>
                                    <button onclick="deleteItem({{ $note->id }})" class="thread-delete-btn" title="Delete">&times;</button>
                                </div>
                                <p class="thread-content">{!! nl2br(e($note->content)) !!}</p>
                            </div>
                        @empty
                            <div class="thread-empty">No notes yet. Write your first update above!</div>
                        @endforelse
                    </div>
                </div>

            </div>
        @else
            <div class="ws-empty-state">
                <div class="ws-empty-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <p>Select a task to open workspace</p>
                <span>Or create a new task to get started</span>
            </div>
        @endif
    </main>
</div>

{{-- ===== MODALS ===== --}}

{{-- New Project Modal --}}
<div id="modal-project" class="todo-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="project-modal-title">
    <div class="todo-modal" style="max-width:340px;">
        <h3 class="todo-modal-title" id="project-modal-title">New Project</h3>
        <div class="form-group">
            <label class="form-label">Project Name</label>
            <input type="text" id="project-name-input" class="form-input" placeholder="e.g. Codebook, API Client">
        </div>
        <div class="form-group">
            <label class="form-label">Project Color</label>
            <input type="color" id="project-color-input" value="#6366f1" style="height:34px;width:52px;border:1px solid var(--border);border-radius:6px;cursor:pointer;background:none;padding:3px;">
        </div>
        <div class="modal-footer">
            <button class="icon-btn" onclick="closeModals()">Cancel</button>
            <button class="primary-btn" onclick="submitNewProject()">Create Project</button>
        </div>
    </div>
</div>

{{-- New Task Modal --}}
<div id="modal-task" class="todo-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="task-modal-title">
    <div class="todo-modal">
        <h3 class="todo-modal-title" id="task-modal-title">New Task</h3>

        @if($currentProject)
            <div style="display:flex;align-items:center;gap:7px;margin-bottom:14px;padding:8px 10px;background:var(--todo-accent-soft);border:1px solid var(--todo-accent-muted);border-radius:var(--todo-radius-sm);font-size:12px;color:var(--text-secondary);">
                <span class="project-dot" style="background:{{ $currentProject->color }};"></span>
                Adding to: <strong style="color:var(--text);">{{ $currentProject->name }}</strong>
            </div>
        @endif

        <div class="form-group">
            <label class="form-label">Task Title</label>
            <input type="text" id="task-title-input" class="form-input" placeholder="e.g. Fix auth token refresh">
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea id="task-desc-input" class="form-textarea" placeholder="Add details, context, or acceptance criteria... (optional)"></textarea>
        </div>
        <div class="modal-row">
            <div class="form-group">
                <label class="form-label">Project</label>
                <select id="task-project-select" class="form-select">
                    <option value="">No Project</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ request('project_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Priority</label>
                <select id="task-priority-select" class="form-select">
                    <option value="medium">Medium</option>
                    <option value="low">Low</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="icon-btn" onclick="closeModals()">Cancel</button>
            <button class="primary-btn" onclick="submitNewTask()">Save Task</button>
        </div>
    </div>
</div>

{{-- New Command Modal --}}
<div id="modal-command" class="todo-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="command-modal-title">
    <div class="todo-modal">
        <h3 class="todo-modal-title" id="command-modal-title">Add Quick Command</h3>
        <div class="form-group">
            <label class="form-label">Command</label>
            <input type="text" id="command-text-input" class="form-input" placeholder="e.g. docker compose exec app php artisan migrate" style="font-family:'JetBrains Mono',monospace;font-size:12px;">
        </div>
        <div class="modal-footer">
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
    let currentProjectId = @json($currentProjectId ? (int) $currentProjectId : null);
    let activeStatusFilter = @json($currentStatus);
    const todoIndexPath = new URL("{{ route('todos.index') }}", window.location.origin).pathname;
    let todoLoadingRequests = 0;
    let todoLoadingHideTimer;
    let todoLoadingProgressTimer;
    let todoLoadingProgress = 0;

    function setTodoLoading(isLoading) {
        const loadingBar = document.getElementById('todo-loading-bar');

        if (!loadingBar) {
            return;
        }

        if (isLoading) {
            if (todoLoadingRequests === 0) {
                window.clearTimeout(todoLoadingHideTimer);
                window.clearInterval(todoLoadingProgressTimer);
                todoLoadingProgress = .12;
                loadingBar.classList.add('visible');
                loadingBar.style.setProperty('--todo-load-progress', String(todoLoadingProgress));
                loadingBar.setAttribute('aria-valuenow', '12');
                todoLoadingProgressTimer = window.setInterval(() => {
                    todoLoadingProgress = Math.min(.9, todoLoadingProgress + ((1 - todoLoadingProgress) * .08));
                    loadingBar.style.setProperty('--todo-load-progress', String(todoLoadingProgress));
                    loadingBar.setAttribute('aria-valuenow', String(Math.round(todoLoadingProgress * 100)));
                }, 160);
            }

            todoLoadingRequests += 1;
            return;
        }

        todoLoadingRequests = Math.max(0, todoLoadingRequests - 1);

        if (todoLoadingRequests === 0) {
            window.clearInterval(todoLoadingProgressTimer);
            todoLoadingProgress = 1;
            loadingBar.style.setProperty('--todo-load-progress', '1');
            loadingBar.setAttribute('aria-valuenow', '100');
            todoLoadingHideTimer = window.setTimeout(() => {
                loadingBar.classList.remove('visible');
                loadingBar.style.setProperty('--todo-load-progress', '0');
            }, 650);
        }
    }

    function waitForTodoLoadingPaint() {
        return new Promise((resolve) => {
            window.requestAnimationFrame(() => {
                window.requestAnimationFrame(resolve);
            });
        });
    }

    const todoInitialLoadingStartedAt = performance.now();

    setTodoLoading(true);
    function finishInitialTodoLoading() {
        const minimumVisibleTime = 1200;
        const elapsedTime = performance.now() - todoInitialLoadingStartedAt;
        const remainingTime = Math.max(0, minimumVisibleTime - elapsedTime);

        window.setTimeout(() => setTodoLoading(false), remainingTime);
    }

    if (document.readyState === 'complete') {
        finishInitialTodoLoading();
    } else {
        window.addEventListener('load', finishInitialTodoLoading, { once: true });
    }

    window.addEventListener('beforeunload', () => {
        window.clearTimeout(todoLoadingHideTimer);
        todoLoadingRequests = 0;
        setTodoLoading(true);
    });

    function showToast(message) {
        const toast = document.getElementById('todo-toast');
        toast.textContent = message;
        toast.classList.add('visible');
        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => toast.classList.remove('visible'), 4000);
    }

    async function todoRequest(url, options) {
        setTodoLoading(true);
        await waitForTodoLoadingPaint();

        try {
            const response = await fetch(url, options);

            if (response.ok) {
                return response;
            }

            const payload = await response.json().catch(() => ({}));
            const message = payload.message || Object.values(payload.errors || {}).flat()[0] || 'Perubahan tidak dapat disimpan.';
            throw new Error(message);
        } finally {
            setTodoLoading(false);
        }
    }

    async function refreshTodoShell(url = window.location.href, { pushState = false } = {}) {
        setTodoLoading(true);
        await waitForTodoLoadingPaint();

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            });

            if (!response.ok) {
                throw new Error('Workspace tidak dapat diperbarui.');
            }

            const html = await response.text();
            const documentParser = new DOMParser();
            const nextDocument = documentParser.parseFromString(html, 'text/html');
            const currentShell = document.querySelector('.todo-shell');
            const nextShell = nextDocument.querySelector('.todo-shell');

            if (!currentShell || !nextShell) {
                throw new Error('Workspace response tidak valid.');
            }

            currentShell.replaceWith(nextShell);

            ['modal-project', 'modal-task', 'modal-command', 'todo-toast'].forEach((id) => {
                const currentElement = document.getElementById(id);
                const nextElement = nextDocument.getElementById(id);

                if (currentElement && nextElement) {
                    currentElement.replaceWith(nextElement);
                }
            });

            activeTodoId = Number(nextShell.dataset.activeTodoId) || null;
            currentProjectId = Number(nextShell.dataset.projectId) || null;
            activeStatusFilter = nextShell.dataset.statusFilter || null;

            if (pushState) {
                window.history.pushState({}, '', url);
            }

            bindTodoTitleEditor();
        } finally {
            setTodoLoading(false);
        }
    }

    function navigateTodo(event, link) {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return true;
        }

        event.preventDefault();
        closeModals();
        refreshTodoShell(link.href, { pushState: true }).catch((error) => showToast(error.message));

        return false;
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
            await refreshTodoShell();
        } catch (error) {
            showToast(error.message);
        }
    }

    async function submitNewTask() {
        const title = document.getElementById('task-title-input').value.trim();
        const description = document.getElementById('task-desc-input').value.trim();
        const todo_project_id = currentProjectId ?? (document.getElementById('task-project-select').value || null);
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
            await refreshTodoShell("{{ route('todos.index') }}?active=" + data.todo.id, { pushState: true });
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
                const statusSelect = document.querySelector('.ws-status-select');

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
            await refreshTodoShell();
        } catch (error) {
            showToast(error.message);
        }
    }

    function startTaskTitleEdit() {
        const title = document.getElementById('ws-task-title');
        const editor = document.getElementById('ws-task-title-editor');
        const input = document.getElementById('ws-task-title-input');

        if (!title || !editor || !input) {
            return;
        }

        title.style.display = 'none';
        document.getElementById('edit-task-title-btn').style.display = 'none';
        editor.style.display = 'block';
        input.focus();
        input.select();
    }

    function cancelTaskTitleEdit() {
        const title = document.getElementById('ws-task-title');
        const editor = document.getElementById('ws-task-title-editor');
        const input = document.getElementById('ws-task-title-input');

        if (!title || !editor || !input) {
            return;
        }

        input.value = title.textContent.trim();
        title.style.display = '';
        document.getElementById('edit-task-title-btn').style.display = '';
        editor.style.display = 'none';
    }

    async function saveTaskTitle() {
        const input = document.getElementById('ws-task-title-input');
        const title = document.getElementById('ws-task-title');

        if (!input || !title || !activeTodoId) {
            return;
        }

        const nextTitle = input.value.trim();
        if (!nextTitle) {
            showToast('Judul task wajib diisi.');
            input.focus();
            return;
        }

        try {
            const response = await todoRequest(`/todos/${activeTodoId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ title: nextTitle })
            });
            const data = await response.json();

            title.textContent = data.todo.title;
            document.querySelector(`#task-card-${activeTodoId} .task-title-label`)?.replaceChildren(document.createTextNode(data.todo.title));
            cancelTaskTitleEdit();
        } catch (error) {
            showToast(error.message);
        }
    }

    function bindTodoTitleEditor() {
        document.getElementById('ws-task-title-input')?.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                saveTaskTitle();
            }

            if (event.key === 'Escape') {
                cancelTaskTitleEdit();
            }
        });
    }

    bindTodoTitleEditor();

    async function deleteActiveTask(todoId) {
        if (!confirm('Yakin ingin menghapus task ini?')) return;
        try {
            await todoRequest(`/todos/${todoId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            await refreshTodoShell("{{ route('todos.index') }}", { pushState: true });
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
            await refreshTodoShell();
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
            await refreshTodoShell();
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
            await refreshTodoShell();
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

    document.addEventListener('click', (event) => {
        if (event.target.classList?.contains('todo-modal-overlay')) {
            closeModals();
        }

        const link = event.target.closest('a');

        if (!link || !link.closest('.todo-shell') || event.defaultPrevented || event.button !== 0) {
            return;
        }

        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const url = new URL(link.href, window.location.href);

        if (url.origin !== window.location.origin || url.pathname !== todoIndexPath) {
            return;
        }

        event.preventDefault();
        closeModals();
        refreshTodoShell(url.href, { pushState: true }).catch((error) => showToast(error.message));
    });

    window.addEventListener('popstate', () => {
        refreshTodoShell(window.location.href).catch((error) => showToast(error.message));
    });

    // Theme toggle
    document.getElementById('theme-btn')?.addEventListener('click', () => {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme') || 'dark';
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('codebook_theme', newTheme);
    });
</script>
@endsection
