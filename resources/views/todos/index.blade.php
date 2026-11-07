@extends('layouts.app')

@section('title', 'Tasks & Projects')

@section('styles')
<style>
/* =========================================================
   TODOS PAGE — Project Sidebar, Status Board, Task Details
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

.todo-shell.card-modal-open { overflow: visible; }

body:has(.todo-shell.card-modal-open) { overflow: hidden; }

.task-modal-backdrop {
    position: fixed;
    inset: 52px 0 0;
    z-index: 1100;
    width: 100%;
    padding: 0;
    border: 0;
    background: rgba(8, 12, 24, .58);
    backdrop-filter: blur(2px);
    cursor: default;
    animation: task-modal-backdrop-in .16s ease-out;
}

.task-modal-backdrop[hidden] { display: none; }

.todo-shell.card-modal-open .pane-workspace {
    position: fixed;
    z-index: 1101;
    top: calc(52px + (100vh - 52px) / 2);
    left: 50%;
    width: min(1120px, calc(100vw - 48px));
    min-width: 0;
    height: min(850px, calc(100vh - 96px));
    max-height: calc(100vh - 96px);
    transform: translate(-50%, -50%);
    overflow-x: hidden;
    overflow-y: auto;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: var(--bg);
    box-shadow: 0 24px 80px rgba(0, 0, 0, .36);
    animation: task-modal-in .18s cubic-bezier(.2,.8,.2,1);
}

.task-modal-toolbar {
    position: sticky;
    top: 0;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 38px;
    padding: 0 12px;
    border-bottom: 1px solid var(--border);
    background: color-mix(in srgb, var(--bg) 94%, transparent);
    backdrop-filter: blur(12px);
}

.task-modal-toolbar-label {
    color: var(--muted);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
}

.task-modal-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    padding: 0;
    border: 1px solid transparent;
    border-radius: 7px;
    color: var(--muted);
    background: transparent;
    cursor: pointer;
}

.task-modal-close:hover,
.task-modal-close:focus-visible {
    color: var(--text);
    background: var(--panel-2);
    border-color: var(--border);
}

.task-modal-close:focus-visible { outline: 2px solid var(--todo-accent); outline-offset: 2px; }

@keyframes task-modal-backdrop-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes task-modal-in { from { opacity: 0; transform: translate(-50%, calc(-50% + 8px)) scale(.99); } to { opacity: 1; transform: translate(-50%, -50%) scale(1); } }

@media (prefers-reduced-motion: reduce) {
    .task-modal-backdrop,
    .todo-shell.card-modal-open .pane-workspace { animation: none; }
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

/* Pane 1: Workspaces and projects */
.pane-projects {
    width: 240px;
    min-width: 200px;
    flex-shrink: 0;
    background: var(--sidebar-bg);
    border-right: 1px solid var(--border);
}

/* Pane 2: Task List */
.pane-list {
    flex: 1;
    min-width: 0;
    background: var(--bg);
    border-right: 1px solid var(--border);
}

.project-picker {
    align-items: center;
    justify-content: center;
    padding: 32px;
    text-align: center;
}

.project-picker-card {
    width: min(100%, 480px);
    padding: 40px 32px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: var(--panel);
    box-shadow: var(--shadow-sm);
}

.project-picker-icon {
    display: grid;
    place-items: center;
    width: 52px;
    height: 52px;
    margin: 0 auto 18px;
    border: 1px solid var(--todo-accent-muted);
    border-radius: 15px;
    color: var(--todo-accent);
    background: var(--todo-accent-soft);
}

.project-picker h2 {
    margin: 0 0 9px;
    color: var(--text);
    font-size: 20px;
    letter-spacing: -.03em;
}

.project-picker p {
    margin: 0;
    color: var(--muted);
    font-size: 13px;
    line-height: 1.7;
}

@media (max-width: 640px) {
    .project-picker { padding: 20px; }
    .project-picker-card { padding: 32px 22px; }
}

/* Pane 3: Workspace */
.pane-workspace {
    width: 360px;
    min-width: 320px;
    flex-shrink: 0;
    background: var(--bg);
    overflow-y: auto;
}

.pane-workspace.is-empty { display: none; }
.todo-detail-shell .pane-workspace { width: 100%; min-width: 0; }
.todo-detail-shell .task-workspace-content { max-width: 900px; margin: 0 auto; }

/* ---- Kanban Board ---- */
.board-header { padding-inline: 20px; }
.board-header .pane-header-title { font-size: 14px; }
.board-scroll {
    flex: 1;
    min-height: 0;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    overflow: auto;
    padding: 18px 20px 24px;
}
.board-column {
    display: flex;
    flex-direction: column;
    width: 248px;
    min-width: 248px;
    height: 100%;
    max-height: 100%;
    border: 1px solid var(--border);
    border-radius: 12px;
    background: color-mix(in srgb, var(--panel) 78%, var(--bg));
    transition: border-color var(--todo-transition), background var(--todo-transition);
}
.board-column.drag-over { border-color: var(--todo-accent); background: var(--todo-accent-soft); }
.board-column-header {
    display: flex;
    align-items: center;
    gap: 8px;
    height: auto;
    padding: 13px 12px 11px;
    background: transparent;
    border-bottom: 1px solid var(--border);
    box-shadow: none;
    position: static;
}
.board-column-dot { width: 8px; height: 8px; border-radius: 50%; flex: 0 0 auto; }
.board-column-title { font-size: 11.5px; font-weight: 700; color: var(--text); flex: 1; }
.board-column-title[role="button"] { cursor: text; }
.board-column-title[role="button"]:focus-visible { outline: 2px solid var(--todo-accent); outline-offset: 3px; border-radius: 3px; }
.board-column-title-input { flex: 1; min-width: 0; padding: 2px 5px; border: 1px solid var(--todo-accent); border-radius: 5px; background: var(--panel); color: var(--text); font: inherit; outline: none; }
.board-column-count { font-size: 10px; font-weight: 700; color: var(--muted); background: var(--panel-2); border: 1px solid var(--border); border-radius: 7px; padding: 2px 7px; font-variant-numeric: tabular-nums; }
.board-column-add { border: 0; background: transparent; color: var(--muted); width: 24px; height: 24px; border-radius: 6px; font-size: 18px; cursor: pointer; line-height: 1; }
.board-column-add:hover { color: var(--text); background: var(--panel-hover); }
.board-column-menu { position: relative; display: inline-flex; flex: 0 0 auto; }
.board-list-menu-toggle { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; padding: 0; border: 0; border-radius: 7px; color: var(--muted); background: transparent; cursor: pointer; }
.board-list-menu-toggle:hover, .board-list-menu-toggle[aria-expanded="true"] { color: var(--text); background: var(--panel-hover); }
.board-list-menu-toggle:focus-visible, .board-list-menu-item:focus-visible { outline: 2px solid var(--todo-accent); outline-offset: 2px; }
.board-list-menu { position: absolute; top: calc(100% + 4px); right: 0; z-index: 20; min-width: 144px; padding: 5px; border: 1px solid var(--border); border-radius: 9px; background: var(--panel); box-shadow: 0 10px 28px rgba(0,0,0,.18); }
.board-list-menu[hidden] { display: none; }
.board-list-menu-item { display: flex; align-items: center; width: 100%; min-height: 34px; padding: 0 9px; border: 0; border-radius: 6px; color: #ef4444; background: transparent; font: inherit; font-size: 12px; text-align: left; cursor: pointer; }
.board-list-menu-item:hover { background: rgba(239,68,68,.1); }
.board-list-menu-item:disabled { color: var(--muted); cursor: not-allowed; opacity: .65; }
.board-list-menu-item:disabled:hover { background: transparent; }
.board-column-cards { position: relative; display: flex; flex: 1 1 auto; flex-direction: column; gap: 8px; padding: 9px; overflow-x: hidden; overflow-y: auto; min-height: 54px; scrollbar-gutter: stable; }
.board-column-empty { padding: 12px 8px; text-align: center; font-size: 10.5px; color: var(--muted); border: 1px dashed var(--border); border-radius: 8px; }
.board-task-card { display: flex; flex: 0 0 auto; gap: 9px; padding: 11px; min-height: 0; border: 1px solid var(--border); border-radius: 9px; background: var(--panel); box-shadow: 0 1px 2px rgba(0,0,0,.04); cursor: grab; }
.board-task-card.dragging, .board-task-card.dragging:hover { opacity: .32; border-style: dashed; box-shadow: none; transform: none; }
.todo-drop-placeholder { position: absolute; z-index: 6; left: 9px; right: 9px; height: 3px; border-radius: 999px; background: var(--todo-accent); box-shadow: 0 0 0 3px var(--todo-accent-soft); pointer-events: none; transition: top 70ms ease-out; }
.board-task-card.reorder-confirmed { animation: todo-card-reorder-confirmed 240ms ease-out; }
@keyframes todo-card-reorder-confirmed {
    0% { background: var(--todo-accent-soft); border-color: var(--todo-accent); }
    100% { background: var(--panel); border-color: var(--border); }
}
.board-task-card::before { display: none; }
.board-task-card:hover { border-color: var(--border-hover); background: var(--panel); box-shadow: 0 4px 12px rgba(0,0,0,.07); transform: translateY(-1px); }
.board-task-card:active { cursor: grabbing; }
.board-task-card.active { border-color: var(--todo-accent-muted); box-shadow: 0 0 0 2px var(--todo-accent-soft); background: var(--panel); }
.board-task-card .task-card-link { min-height: 100%; }
.board-task-card .task-title-text { font-size: 12px; line-height: 1.45; }
.board-task-card .task-title-text { white-space: normal; overflow: visible; text-overflow: clip; }
.board-task-card .task-card-meta { margin-top: 9px; gap: 6px; flex-wrap: wrap; }
.board-task-card .task-checkbox { width: 15px; height: 15px; }

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

.workspace-group { margin: 4px 0 8px; }
.workspace-group-header { display: flex; align-items: center; gap: 4px; }
.workspace-group-header:hover .project-menu-toggle, .workspace-group-header .project-actions:focus-within .project-menu-toggle { opacity: 1; visibility: visible; }
.workspace-link { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; padding: 8px 9px; border: 1px solid transparent; border-radius: 7px; color: var(--text-secondary); font-size: 12px; font-weight: 650; text-decoration: none; }
.workspace-link:hover, .workspace-link.active { color: var(--text); background: var(--panel-hover); }
.workspace-link.active { border-color: var(--border); background: var(--panel); }
.workspace-toggle { width: 30px; height: 30px; border: 0; border-radius: 6px; color: var(--muted); background: transparent; cursor: pointer; transition: transform var(--todo-transition), background var(--todo-transition); }
.workspace-toggle:hover { color: var(--text); background: var(--panel-hover); }
.workspace-toggle[aria-expanded="true"] { transform: rotate(90deg); }
.workspace-projects { padding: 2px 0 5px 12px; margin-left: 13px; border-left: 1px solid var(--border); }
.workspace-projects[hidden] { display: none; }
.workspace-projects .project-link { padding-block: 6px; font-size: 11.5px; }
.workspace-create-project { width: 100%; margin-top: 3px; padding: 6px 9px; border: 0; border-radius: 6px; color: var(--muted); background: transparent; font: inherit; font-size: 11px; text-align: left; cursor: pointer; }
.workspace-create-project:hover { color: var(--text); background: var(--panel-hover); }
.project-row { display: flex; align-items: center; gap: 2px; }
.project-row > .project-link { flex: 1; min-width: 0; }
.project-actions { position: relative; flex: 0 0 auto; }
.project-menu-toggle { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; padding: 0; border: 0; border-radius: 6px; color: var(--muted); background: transparent; cursor: pointer; opacity: 0; visibility: visible; }
.project-row:hover .project-menu-toggle, .project-actions:focus-within .project-menu-toggle, .project-menu-toggle[aria-expanded="true"] { opacity: 1; visibility: visible; }
.project-menu-toggle:hover, .project-menu-toggle[aria-expanded="true"] { color: var(--text); background: var(--panel-hover); }
.project-menu-toggle:focus-visible, .project-menu-item:focus-visible { outline: 2px solid var(--todo-accent); outline-offset: 2px; }
.project-menu { position: absolute; top: calc(100% + 4px); right: 0; z-index: 30; min-width: 156px; padding: 5px; border: 1px solid var(--border); border-radius: 9px; background: var(--panel); box-shadow: 0 10px 28px rgba(0,0,0,.18); }
.project-menu[hidden] { display: none; }
.project-menu-item { display: flex; align-items: center; width: 100%; min-height: 34px; padding: 0 9px; border: 0; border-radius: 6px; color: var(--text-secondary); background: transparent; font: inherit; font-size: 12px; text-align: left; cursor: pointer; }
.project-menu-item:hover { color: var(--text); background: var(--panel-hover); }
.project-menu-item.danger { color: #ef4444; }
.project-menu-item.danger:hover { background: rgba(239,68,68,.1); }

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
    text-decoration: none;
}

.task-card.done .task-title-label {
    text-decoration: line-through;
    text-decoration-color: currentColor;
    text-decoration-skip-ink: none;
    text-decoration-thickness: 1.5px;
}

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

.todo-shell.card-modal-open .task-workspace-content {
    display: flex;
    box-sizing: border-box;
    flex-direction: column;
    width: 100%;
    max-width: none;
    min-height: 100%;
    padding: 0;
}

.task-detail-columns {
    display: grid;
    grid-template-columns: minmax(0, .92fr) minmax(0, 1.08fr);
    align-items: stretch;
    flex: 1;
    min-height: 0;
}

.task-detail-left-column {
    min-width: 0;
    padding: 12px 16px 16px;
    border-right: 1px solid var(--border);
}

.task-description-section { min-height: 0; padding-bottom: 14px !important; border-bottom: 1px solid var(--border) !important; margin-bottom: 14px !important; }
.todo-shell.card-modal-open .task-detail-left-column .ws-section {
    padding: 0;
    margin: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}

.todo-shell.card-modal-open .task-detail-left-column .ws-section:first-child {
    padding-bottom: 14px;
    margin-bottom: 14px;
    border-bottom: 1px solid var(--border);
}

.todo-shell.card-modal-open .task-comments-section {
    display: flex;
    box-sizing: border-box;
    flex-direction: column;
    min-width: 0;
    min-height: 100%;
    padding: 14px 16px;
    margin: 0;
    border: 0;
    border-left: 1px solid var(--border);
    border-radius: 0;
    background: color-mix(in srgb, var(--panel) 88%, var(--text));
}

.todo-shell.card-modal-open .ws-header-card {
    padding: 0 0 12px;
    margin-bottom: 12px;
    border: 0;
    border-bottom: 1px solid var(--border);
    border-radius: 0;
    background: transparent;
    box-shadow: none;
}
.todo-shell.card-modal-open .ws-header-top { gap: 10px; }
.todo-shell.card-modal-open .ws-header-badges { margin-bottom: 6px; }
.todo-shell.card-modal-open .ws-section { padding: 14px 15px; margin-bottom: 10px; }
.todo-shell.card-modal-open .ws-section-header { margin-bottom: 10px; }
.todo-shell.card-modal-open .task-description-section { padding: 0 0 14px; margin: 0 0 14px; }
.todo-shell.card-modal-open .task-comments-section { padding: 14px 16px; margin: 0; }
.todo-shell.card-modal-open #thread-stream-container { flex: 1; min-height: 0; overflow-y: auto; }
.thread-entry-actions { display: inline-flex; align-items: center; gap: 4px; }

.thread-edit-btn,
.thread-delete-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 26px;
    height: 26px;
    padding: 0 6px;
    border: 1px solid transparent;
    border-radius: 5px;
    color: var(--muted);
    background: transparent;
    cursor: pointer;
    font: inherit;
    font-size: 11px;
    transition: color var(--todo-transition), background var(--todo-transition), border-color var(--todo-transition);
}

.thread-edit-btn:hover { color: var(--todo-accent); background: var(--todo-accent-soft); border-color: var(--todo-accent-muted); }
.thread-delete-btn:hover { color: #f87171; background: rgba(239,68,68,.08); border-color: rgba(239,68,68,.2); }

.thread-edit-wrap[hidden] { display: none; }
.thread-edit-textarea {
    width: 100%;
    min-height: 96px;
    padding: 9px 10px;
    border: 1px solid var(--todo-accent-muted);
    border-radius: 7px;
    color: var(--text);
    background: var(--panel);
    font: inherit;
    font-size: 12px;
    line-height: 1.6;
    resize: vertical;
    outline: none;
}

.thread-edit-textarea:focus { border-color: var(--todo-accent); box-shadow: 0 0 0 3px var(--todo-accent-soft); }
.thread-edit-actions { display: flex; justify-content: flex-end; gap: 6px; margin-top: 7px; }
.thread-edit-actions button { height: 27px; padding: 0 9px; font-size: 11px; }

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
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: var(--todo-radius-sm);
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 11.5px;
    font-weight: 600;
    font-family: 'Inter', sans-serif;
    padding: 6px 9px;
    transition: all var(--todo-transition);
    flex-shrink: 0;
}

.ws-edit-title-btn:hover {
    background: var(--todo-accent-soft);
    border-color: var(--todo-accent-muted);
    color: var(--todo-accent);
}

.ws-edit-title-btn:focus-visible {
    outline: 2px solid var(--todo-accent);
    outline-offset: 2px;
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
    z-index: 1200;
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

#todo-confirm-modal { z-index: 1300; }
.todo-confirm-card { box-sizing: border-box; width: min(100%, 410px); padding: 22px; border: 1px solid var(--border); border-radius: 16px; background: var(--panel); box-shadow: 0 24px 72px rgba(0,0,0,.42), 0 0 0 1px rgba(255,255,255,.035); animation: modalIn .16s cubic-bezier(.2,.8,.2,1); }
.todo-confirm-heading { display: flex; align-items: flex-start; gap: 13px; }
.todo-confirm-icon { display: grid; flex: 0 0 40px; place-items: center; width: 40px; height: 40px; border: 1px solid rgba(239,68,68,.2); border-radius: 12px; color: #ef4444; background: rgba(239,68,68,.1); }
.todo-confirm-copy { min-width: 0; padding-top: 1px; }
.todo-confirm-title { margin: 0; color: var(--text); font-size: 15px; font-weight: 700; letter-spacing: -.02em; }
.todo-confirm-message { margin: 6px 0 0; color: var(--text-secondary); font-size: 12.5px; line-height: 1.6; overflow-wrap: anywhere; }
.todo-confirm-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 22px; }
.todo-confirm-cancel, .todo-confirm-submit { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; padding: 0 13px; border-radius: 8px; font: inherit; font-size: 12px; font-weight: 650; cursor: pointer; transition: background var(--todo-transition), border-color var(--todo-transition), color var(--todo-transition), transform var(--todo-transition); }
.todo-confirm-cancel { border: 1px solid var(--border); color: var(--text-secondary); background: var(--panel); }
.todo-confirm-cancel:hover { border-color: var(--border-hover); color: var(--text); background: var(--panel-hover); }
.todo-confirm-submit { border: 1px solid #dc2626; color: #fff; background: #dc2626; box-shadow: 0 3px 10px rgba(220,38,38,.2); }
.todo-confirm-submit:hover { border-color: #b91c1c; background: #b91c1c; transform: translateY(-1px); }
.todo-confirm-cancel:focus-visible, .todo-confirm-submit:focus-visible { outline: 2px solid var(--todo-accent); outline-offset: 2px; }

@media (prefers-reduced-motion: reduce) {
    .todo-confirm-card { animation: none; }
    .todo-confirm-cancel, .todo-confirm-submit { transition: none; }
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

.required-marker {
    margin-left: 6px;
    color: #dc2626;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0;
    text-transform: none;
}

.form-textarea.is-invalid {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, .12);
}

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
    z-index: 1200;
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

.todo-workspace-skeleton {
    display: grid;
    gap: 18px;
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
    padding: 26px 24px;
}

.todo-skeleton-block {
    position: relative;
    overflow: hidden;
    border-radius: 7px;
    background: var(--panel-2);
}

.todo-skeleton-block::after {
    position: absolute;
    inset: 0;
    content: '';
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--text) 7%, transparent), transparent);
    animation: todo-skeleton-shimmer 1.35s ease-in-out infinite;
}

.todo-skeleton-meta { width: 42%; height: 14px; }
.todo-skeleton-title { width: 68%; height: 30px; }
.todo-skeleton-copy { width: 88%; height: 13px; }
.todo-skeleton-copy.short { width: 54%; }
.todo-skeleton-section { height: 112px; margin-top: 4px; border: 1px solid var(--border); background: var(--panel); }

.todo-board-skeleton { display: flex; flex: 1; flex-direction: column; min-width: 0; min-height: 0; background: var(--bg); }
.todo-board-skeleton-header { display: flex; flex: 0 0 52px; align-items: center; justify-content: space-between; padding: 0 20px; border-bottom: 1px solid var(--border); }
.todo-board-skeleton-title { width: 190px; height: 14px; }
.todo-board-skeleton-action { width: 92px; height: 30px; border-radius: 6px; }
.todo-board-skeleton-columns { display: flex; flex: 1; min-height: 0; gap: 14px; overflow-x: auto; overflow-y: hidden; padding: 18px 20px 24px; }
.todo-board-skeleton-column { display: flex; flex: 0 0 248px; flex-direction: column; gap: 12px; min-height: 0; padding: 12px; border: 1px solid var(--border); border-radius: 12px; background: color-mix(in srgb, var(--panel) 78%, var(--bg)); }
.todo-board-skeleton-list-title { width: 58%; height: 14px; margin: 2px 0 3px; }
.todo-board-skeleton-card { height: 76px; flex: 0 0 auto; border: 1px solid var(--border); border-radius: 9px; background: var(--panel); }
.todo-board-skeleton-card.short { height: 60px; }
.todo-board-skeleton-card.tall { height: 92px; }

@keyframes todo-skeleton-shimmer {
    100% { transform: translateX(100%); }
}

@media (prefers-reduced-motion: reduce) {
    .todo-skeleton-block::after { animation: none; }
}

/* ---- Responsive ---- */
@media (max-width: 1100px) {
    .pane-projects { width: 200px; min-width: 160px; }
    .pane-workspace { width: 320px; min-width: 290px; }
    .board-scroll { padding-inline: 14px; gap: 10px; }
    .board-column { width: 230px; min-width: 230px; }
}

@media (max-width: 860px) {
    .todo-board-skeleton-header { flex-basis: 48px; padding-inline: 14px; }
    .todo-board-skeleton-columns { gap: 10px; padding: 12px 14px 16px; overflow-x: auto; }
    .todo-board-skeleton-column { flex-basis: min(82vw, 300px); }
}

@media (max-width: 860px) {
    .todo-shell { display: flex; flex-direction: column; height: auto; min-height: calc(100vh - 52px); overflow: visible; }
    body:has(.todo-shell) { overflow: auto; }
    .pane-projects { width: 100%; min-width: 0; border-right: none; border-bottom: 1px solid var(--border); }
    .pane-projects > .pane-header { height: 42px; }
    .project-list-scroll { display: block; flex: 0 0 auto; overflow-x: hidden; overflow-y: auto; padding: 6px 8px; max-height: 180px; }
    .workspace-group { margin: 2px 0 5px; }
    .workspace-projects { padding-left: 8px; }
    .project-menu-toggle { opacity: 1; visibility: visible; }
    .pane-list { flex: 0 0 390px; width: 100%; border-right: none; }
    .board-header { height: 48px; padding-inline: 14px; }
    .board-scroll { padding: 12px 14px 16px; }
    .board-column { width: min(82vw, 300px); min-width: min(82vw, 300px); height: 360px; max-height: 360px; }
    .pane-workspace { width: 100%; min-width: 0; min-height: 320px; border-top: 1px solid var(--border); }
    .task-workspace-content { padding: 20px 16px; }
    .ws-header-card { padding: 18px 16px; }
    .task-modal-backdrop { inset: 0; }
    .todo-shell.card-modal-open .pane-workspace {
        top: 50%;
        width: calc(100vw - 20px);
        height: calc(100dvh - 20px);
        max-height: calc(100dvh - 20px);
        border-radius: 12px;
    }
    .todo-shell.card-modal-open .task-workspace-content { min-height: 100%; }
    .todo-shell.card-modal-open .ws-header-card { padding-bottom: 10px; margin-bottom: 10px; }
    .todo-shell.card-modal-open .ws-section { padding: 12px; }
    .task-detail-columns { grid-template-columns: minmax(0, 1fr); flex: none; }
    .task-detail-left-column { padding: 12px; border-right: 0; }
    .todo-shell.card-modal-open .task-comments-section { min-height: 320px; padding: 12px; border-top: 1px solid var(--border); border-left: 0; }
}
</style>
@endsection

@section('content')
{{-- Loading Bar --}}
<div id="todo-loading-bar" class="todo-loading-bar visible" role="progressbar" aria-label="Loading" aria-valuemin="0" aria-valuemax="100" aria-valuenow="8" style="--todo-load-progress:.08"></div>

{{-- ===== HEADER ===== --}}
<header>
    <div class="brand">
        <a href="{{ $detailOnly ? route('todos.index', request()->only(['workspace_id', 'project_id', 'status'])) : route('workspace') }}" style="color:var(--muted);text-decoration:none;font-size:12px;display:inline-flex;align-items:center;gap:4px;padding-right:12px;border-right:1px solid var(--border);">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            {{ $detailOnly ? 'Board' : 'Notes' }}
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
<div class="todo-shell {{ $detailOnly ? 'todo-detail-shell' : '' }} {{ $activeTodo ? 'card-modal-open' : '' }}" data-active-todo-id="{{ $activeTodo?->id ?? '' }}" data-project-id="{{ $currentProjectId ?? '' }}" data-workspace-id="{{ $currentWorkspaceId ?? '' }}">

    @if(!$detailOnly)
    {{-- ===== PANE 1: WORKSPACES AND PROJECTS ===== --}}
    <aside class="pane-projects">
        <div class="pane-header">
            <span class="pane-header-title">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                Workspaces
            </span>
            <button class="pane-add-btn" onclick="openNewWorkspaceModal()" title="New workspace">+</button>
        </div>

        <div class="project-list-scroll">
            @foreach($workspaces as $workspace)
                <div class="workspace-group">
                    <div class="workspace-group-header">
                        <a href="{{ route('todos.index', ['workspace_id' => $workspace->id]) }}" onclick="return navigateTodo(event, this)" class="workspace-link {{ $currentWorkspaceId === $workspace->id ? 'active' : '' }}">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="8" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/><rect x="13" y="13" width="8" height="8" rx="1.5"/></svg>
                            <span class="project-link-name">{{ $workspace->name }}</span>
                        </a>
                        <div class="project-actions">
                            <button class="project-menu-toggle" type="button" aria-label="Workspace options for {{ $workspace->name }}" aria-haspopup="menu" aria-expanded="false" onclick="toggleTodoProjectMenu(event, this)">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="19" cy="12" r="1.8"></circle></svg>
                            </button>
                            <div class="project-menu" role="menu" aria-label="Workspace options for {{ $workspace->name }}" hidden>
                                <button class="project-menu-item" type="button" role="menuitem" data-workspace-id="{{ $workspace->id }}" data-workspace-name="{{ $workspace->name }}" onclick="openEditWorkspaceModal(this)">Rename workspace</button>
                            </div>
                        </div>
                        <button class="workspace-toggle" type="button" aria-label="Show projects in {{ $workspace->name }}" aria-expanded="{{ $currentWorkspaceId === $workspace->id ? 'true' : 'false' }}" onclick="toggleWorkspaceProjects(this, {{ $workspace->id }})"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg></button>
                    </div>
                    <div class="workspace-projects" id="workspace-projects-{{ $workspace->id }}" {{ $currentWorkspaceId === $workspace->id ? '' : 'hidden' }}>
                        @foreach($workspace->projects as $p)
                            <div class="project-row">
                                <a href="{{ route('todos.index', ['workspace_id' => $workspace->id, 'project_id' => $p->id, 'status' => request('status')]) }}" onclick="return navigateTodo(event, this)" class="project-link {{ $currentProject?->id === $p->id ? 'active' : '' }}">
                                    <span class="project-link-left">
                                        <span class="project-dot" style="background:{{ $p->color }};"></span>
                                        <span class="project-link-name">{{ $p->name }}</span>
                                    </span>
                                    <span class="project-count-badge project-pending-count" data-project-id="{{ $p->id }}" data-pending-count="{{ $p->pending_count }}" style="{{ $p->pending_count > 0 ? '' : 'display:none;' }}">{{ $p->pending_count }}</span>
                                </a>
                                <div class="project-actions">
                                    <button class="project-menu-toggle" type="button" aria-label="Project options for {{ $p->name }}" aria-haspopup="menu" aria-expanded="false" onclick="toggleTodoProjectMenu(event, this)">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="19" cy="12" r="1.8"></circle></svg>
                                    </button>
                                    <div class="project-menu" role="menu" aria-label="Project options for {{ $p->name }}" hidden>
                                        <button class="project-menu-item" type="button" role="menuitem" data-project-id="{{ $p->id }}" data-project-name="{{ $p->name }}" data-project-color="{{ $p->color }}" data-workspace-id="{{ $workspace->id }}" onclick="openEditProjectModal(this)">Rename project</button>
                                        <button class="project-menu-item danger" type="button" role="menuitem" data-project-id="{{ $p->id }}" data-project-name="{{ $p->name }}" data-project-card-count="{{ $p->todos_count }}" data-workspace-id="{{ $workspace->id }}" onclick="deleteTodoProject(this)">Delete project</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <button class="workspace-create-project" type="button" onclick="openNewProjectModal({{ $workspace->id }})">+ New project</button>
                    </div>
                </div>
            @endforeach
        </div>

    </aside>

    @if($currentProject)
    {{-- ===== PANE 2: STATUS BOARD ===== --}}
    <section class="pane-list">
        <div class="pane-header board-header">
            <span class="pane-header-title">
                Board
                @if($currentProject)
                    <span class="pane-header-subtitle">/ {{ $currentProject->name }}</span>
                @endif
            </span>
            <button class="primary-btn" onclick="openNewListModal()" style="height:30px;padding:0 11px;font-size:11.5px;">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add list
            </button>
        </div>

        <div class="board-scroll" id="task-list-container">
            @foreach($todoLists as $column)
                @php $columnTodos = $todos->where('todo_list_id', $column->id); @endphp
                <section class="board-column" data-list-id="{{ $column->id }}" data-status="{{ $column->system_status ?? '' }}" ondragover="allowTodoDrop(event)" ondragleave="leaveTodoDrop(event)" ondrop="dropTodoCard(event, {{ $column->id }})" aria-label="{{ $column->name }} list">
                    <header class="board-column-header">
                        <span class="board-column-dot" style="background:{{ $column->color }};"></span>
                        <span class="board-column-title" id="todo-list-title-{{ $column->id }}" role="button" tabindex="0" aria-label="Edit {{ $column->name }} list name" onclick="editTodoListName(event, {{ $column->id }})" onkeydown="editTodoListName(event, {{ $column->id }})">{{ $column->name }}</span>
                        <input class="board-column-title-input" id="todo-list-title-input-{{ $column->id }}" type="text" maxlength="80" value="{{ $column->name }}" aria-label="New list name" style="display:none;" onblur="saveTodoListName({{ $column->id }})" onkeydown="handleTodoListNameKeydown(event)">
                        <span class="board-column-count">{{ $columnTodos->count() }}</span>
                        <button class="board-column-add" type="button" onclick="openNewTaskModal({{ $column->id }})" aria-label="Add card to {{ $column->name }}">+</button>
                        <div class="board-column-menu">
                            <button class="board-list-menu-toggle" type="button" aria-label="More options for {{ $column->name }} list" aria-haspopup="menu" aria-expanded="false" onclick="toggleTodoListMenu(event, this)">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="5" r="1.8"></circle><circle cx="12" cy="12" r="1.8"></circle><circle cx="12" cy="19" r="1.8"></circle></svg>
                            </button>
                            <div class="board-list-menu" role="menu" aria-label="List options for {{ $column->name }}" hidden>
                                <button class="board-list-menu-item" type="button" role="menuitem" onclick="deleteTodoList({{ $column->id }})" @if($columnTodos->isNotEmpty()) disabled title="Move or delete all cards first" @endif>Delete list</button>
                            </div>
                        </div>
                    </header>
                    <div class="board-column-cards">
                        @forelse($columnTodos as $t)
                            <article class="task-card board-task-card {{ $t->status === 'done' ? 'done' : '' }} {{ $activeTodo?->id === $t->id ? 'active' : '' }}" id="task-card-{{ $t->id }}" data-project-id="{{ $t->todo_project_id }}" data-todo-id="{{ $t->id }}" data-list-id="{{ $column->id }}" data-status="{{ $t->status }}" data-persisted-list-id="{{ $column->id }}" data-persisted-status="{{ $t->status }}" draggable="true" ondragstart="startTodoDrag(event, {{ $t->id }})" ondragend="endTodoDrag(event)">
                                <div class="task-checkbox-wrap">
                                    <input type="checkbox" class="task-checkbox" aria-label="Mark {{ $t->title }} done" {{ $t->status === 'done' ? 'checked' : '' }} onclick="event.stopPropagation(); toggleTaskDone({{ $t->id }}, this.checked)">
                                </div>
                                <div class="task-card-body">
                                    <a class="task-card-link" href="{{ route('todos.index', array_merge(request()->query(), ['active' => $t->id])) }}">
                                        <div class="task-title-text"><span class="task-title-label">{{ $t->title }}</span></div>
                                        <div class="task-card-meta">
                                            @if($t->project)
                                                <span style="display:inline-flex;align-items:center;gap:4px;font-size:10.5px;color:var(--muted);">
                                                    <span class="project-dot" style="width:5px;height:5px;background:{{ $t->project->color }};"></span>{{ $t->project->name }}
                                                </span>
                                            @endif
                                            <span class="badge badge-{{ $t->priority }}">{{ $t->priority }}</span>
                                            @if($t->items->count() > 0)
                                                <span class="task-items-count"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>{{ $t->items->count() }}</span>
                                            @endif
                                        </div>
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="board-column-empty">Drop a card here</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </section>
    @else
    <main class="pane-list project-picker" aria-labelledby="project-picker-title">
        <div class="project-picker-card">
            <div class="project-picker-icon" aria-hidden="true">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h4l2 2h7A2.5 2.5 0 0 1 21 9.5v8a2.5 2.5 0 0 1-2.5 2.5h-13A2.5 2.5 0 0 1 3 17.5z"/><path d="M3 10h18"/></svg>
            </div>
            @if($projects->isNotEmpty())
                <h2 id="project-picker-title">{{ $currentWorkspace->name }}</h2>
                <p>Pilih proyek di workspace ini untuk melihat daftar dan kartu.</p>
                <button type="button" class="primary-btn" onclick="openNewProjectModal({{ $currentWorkspaceId }})" style="margin-top:20px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Buat proyek
                </button>
            @else
                <h2 id="project-picker-title">Mulai di {{ $currentWorkspace->name }}</h2>
                <p>Buat proyek untuk mengatur daftar dan kartu di workspace ini.</p>
                <button type="button" class="primary-btn" onclick="openNewProjectModal({{ $currentWorkspaceId }})" style="margin-top:20px;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Buat proyek
                </button>
            @endif
        </div>
    </main>
    @endif
    @endif

    {{-- ===== TASK DETAIL PAGE ===== --}}
    @if($activeTodo)
        <button class="task-modal-backdrop" id="task-modal-backdrop" type="button" onclick="closeTaskModal()" aria-label="Close card details"></button>
    @else
        <button class="task-modal-backdrop" id="task-modal-backdrop" type="button" onclick="closeTaskModal()" aria-label="Close card details" hidden></button>
    @endif
    <main class="pane-workspace {{ $activeTodo ? '' : 'is-empty' }}" id="task-workspace-container" role="{{ $activeTodo ? 'dialog' : 'region' }}" @if($activeTodo) aria-modal="true" aria-labelledby="ws-task-title" tabindex="-1" @endif>
        @if($activeTodo)
            <div class="task-workspace-content">

                <div class="task-modal-toolbar">
                    <span class="task-modal-toolbar-label">Card details</span>
                    <button class="task-modal-close" id="close-task-modal" type="button" onclick="closeTaskModal()" aria-label="Close card details" title="Close card details">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="task-detail-columns">
                    <div class="task-detail-left-column">
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
                                        <select class="ws-status-select" aria-label="Move card to list" onchange="updateTaskList({{ $activeTodo->id }}, this.value)">
                                            @foreach($todoLists as $todoList)
                                                <option value="{{ $todoList->id }}" {{ $activeTodo->todo_list_id === $todoList->id ? 'selected' : '' }}>{{ $todoList->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Title display --}}
                                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;flex-wrap:wrap;">
                                        <h2 class="ws-task-title" id="ws-task-title">{{ $activeTodo->title }}</h2>
                                        <button type="button" class="ws-edit-title-btn" id="edit-task-title-btn" onclick="startTaskTitleEdit()" aria-label="Edit task title" title="Edit task title">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                            <span>Edit title</span>
                                        </button>
                                    </div>

                                    {{-- Title editor --}}
                                    <div class="ws-title-edit-wrap" id="ws-task-title-editor">
                                        <input type="text" id="ws-task-title-input" class="ws-title-input" value="{{ $activeTodo->title }}" maxlength="255" aria-label="Task title">
                                        <div class="ws-title-edit-actions">
                                            <button type="button" class="primary-btn" onclick="saveTaskTitle()" style="font-size:11.5px;height:28px;padding:0 12px;">Save</button>
                                            <button type="button" class="icon-btn" onclick="cancelTaskTitleEdit()" style="font-size:11.5px;height:28px;">Cancel</button>
                                        </div>
                                    </div>
                                </div>

                                <button class="ws-delete-btn" onclick="deleteActiveTask({{ $activeTodo->id }})" title="Delete Task">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                                    Delete
                                </button>
                            </div>
                        </div>

                        <section class="ws-section task-description-section">
                            <div class="ws-section-header">
                                <span class="ws-section-title">Description</span>
                            </div>
                            <p class="ws-task-desc">{{ $activeTodo->description ?: 'No description provided.' }}</p>
                        </section>

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
                    </div>

                    {{-- Comments --}}
                    <div class="ws-section task-comments-section">
                    <div class="ws-section-header">
                        <span class="ws-section-title">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Comments
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
                                        <time class="thread-entry-local-time" datetime="{{ $note->created_at->toIso8601String() }}">
                                            {{ $note->created_at->format('d M Y, H:i') }}
                                        </time>
                                    </span>
                                    <div class="thread-entry-actions">
                                        <button type="button" onclick="editThreadNote({{ $note->id }})" class="thread-edit-btn" aria-label="Edit comment" title="Edit comment">Edit</button>
                                        <button type="button" onclick="deleteItem({{ $note->id }})" class="thread-delete-btn" aria-label="Delete comment" title="Delete comment">&times;</button>
                                    </div>
                                </div>
                                <p class="thread-content" id="thread-content-{{ $note->id }}">{{ $note->content }}</p>
                                <div class="thread-edit-wrap" id="thread-edit-wrap-{{ $note->id }}" hidden>
                                    <textarea class="thread-edit-textarea" id="thread-edit-input-{{ $note->id }}" aria-label="Edit comment" onkeydown="handleThreadNoteKeydown(event, {{ $note->id }})">{{ $note->content }}</textarea>
                                    <div class="thread-edit-actions">
                                        <button class="icon-btn" type="button" onclick="cancelThreadNoteEdit({{ $note->id }})">Cancel</button>
                                        <button class="primary-btn" type="button" onclick="saveThreadNote({{ $note->id }})">Save</button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="thread-empty">No notes yet. Write your first update above!</div>
                        @endforelse
                    </div>
                </div>
                </div>

            </div>
        @else
            <div class="ws-empty-state">
                <div class="ws-empty-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                </div>
                <p>No task selected</p>
                <span>Choose a card from the board.</span>
            </div>
        @endif
    </main>
</div>

{{-- ===== MODALS ===== --}}

{{-- New Workspace Modal --}}
<div id="modal-workspace" class="todo-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="workspace-modal-title">
    <div class="todo-modal" style="max-width:340px;">
        <h3 class="todo-modal-title" id="workspace-modal-title">New Workspace</h3>
        <input type="hidden" id="workspace-id-input" value="">
        <div class="form-group">
            <label class="form-label" for="workspace-name-input">Workspace name</label>
            <input type="text" id="workspace-name-input" class="form-input" maxlength="100" placeholder="e.g. Product, Personal">
        </div>
        <div class="modal-footer">
            <button class="icon-btn" onclick="closeModals()">Cancel</button>
            <button class="primary-btn" id="workspace-submit-button" onclick="submitNewWorkspace()">Create workspace</button>
        </div>
    </div>
</div>

{{-- New Project Modal --}}
<div id="modal-project" class="todo-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="project-modal-title">
    <div class="todo-modal" style="max-width:340px;">
        <h3 class="todo-modal-title" id="project-modal-title">New Project</h3>
        <input type="hidden" id="project-id-input" value="">
        <input type="hidden" id="project-workspace-id" value="{{ $currentWorkspaceId }}">
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
            <button class="primary-btn" id="project-submit-button" onclick="submitNewProject()">Create Project</button>
        </div>
    </div>
</div>

{{-- New List Modal --}}
<div id="modal-list" class="todo-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="list-modal-title">
    <div class="todo-modal" style="max-width:360px;">
        <h3 class="todo-modal-title" id="list-modal-title">Add a list</h3>
        <div class="form-group">
            <label class="form-label" for="list-name-input">List name</label>
            <input type="text" id="list-name-input" class="form-input" maxlength="80" placeholder="e.g. Review, Backlog">
        </div>
        <div class="form-group">
            <label class="form-label" for="list-color-input">List color</label>
            <input type="color" id="list-color-input" value="#6366f1" style="height:34px;width:52px;border:1px solid var(--border);border-radius:6px;cursor:pointer;background:none;padding:3px;">
        </div>
        <div class="modal-footer">
            <button class="icon-btn" onclick="closeModals()">Cancel</button>
            <button class="primary-btn" onclick="submitNewList()">Create list</button>
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
                Adding to project: <strong style="color:var(--text);">{{ $currentProject->name }}</strong>
            </div>
        @endif

        <div class="form-group">
            <label class="form-label" for="task-title-input">Task Title <span class="required-marker">* required</span></label>
            <textarea id="task-title-input" class="form-textarea" rows="3" maxlength="255" placeholder="e.g. Fix auth token refresh" required aria-required="true"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea id="task-desc-input" class="form-textarea" rows="4" placeholder="Add details, context, or acceptance criteria... (optional)"></textarea>
        </div>
        <div class="form-group">
            <label class="form-label" for="task-list-select">List</label>
            <select id="task-list-select" class="form-select" required>
                <option value="" disabled selected>{{ $todoLists->isEmpty() ? 'Create a list first' : 'Choose a list' }}</option>
                @foreach($todoLists as $todoList)
                    <option value="{{ $todoList->id }}">{{ $todoList->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="modal-row">
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

<div id="todo-confirm-modal" class="todo-modal-overlay" role="presentation" aria-hidden="true" onclick="resolveTodoAction(false)">
    <section class="todo-confirm-card" role="alertdialog" aria-modal="true" aria-labelledby="todo-confirm-title" aria-describedby="todo-confirm-message" onclick="event.stopPropagation()">
        <div class="todo-confirm-heading">
            <div class="todo-confirm-icon" aria-hidden="true">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
            </div>
            <div class="todo-confirm-copy">
                <h2 class="todo-confirm-title" id="todo-confirm-title">Hapus item?</h2>
                <p class="todo-confirm-message" id="todo-confirm-message">Tindakan ini tidak dapat dibatalkan.</p>
            </div>
        </div>
        <div class="todo-confirm-footer">
            <button class="todo-confirm-cancel" id="todo-confirm-cancel" type="button" onclick="resolveTodoAction(false)">Batal</button>
            <button class="todo-confirm-submit" id="todo-confirm-submit" type="button" onclick="resolveTodoAction(true)">Hapus</button>
        </div>
    </section>
</div>

<div id="todo-toast" class="todo-toast" role="alert" aria-live="assertive"></div>
@endsection

@section('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let activeTodoId = {{ $activeTodo ? $activeTodo->id : 'null' }};
    let currentProjectId = @json($currentProjectId ? (int) $currentProjectId : null);
    let currentWorkspaceId = @json((int) $currentWorkspaceId);
    const currentTodoStatusFilter = @json(request()->filled('status') ? request('status') : null);
    const todoSystemListIds = @json($todoLists->whereNotNull('system_status')->pluck('id', 'system_status'));
    const todoIndexPath = new URL("{{ route('todos.index') }}", window.location.origin).pathname;
    let todoLoadingRequests = 0;
    let todoLoadingHideTimer;
    let todoLoadingProgressTimer;
    let todoLoadingProgress = 0;
    let todoShellRefreshId = 0;
    let todoWorkspaceSnapshot = null;
    let todoBoardSnapshot = null;
    let todoModalFocusAfterCloseId = null;
    let todoConfirmResolver = null;
    let todoConfirmTrigger = null;

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

    function confirmTodoAction({ title, message, confirmLabel = 'Hapus' }) {
        if (todoConfirmResolver) {
            resolveTodoAction(false, false);
        }

        return new Promise((resolve) => {
            const modal = document.getElementById('todo-confirm-modal');
            todoConfirmResolver = resolve;
            todoConfirmTrigger = document.activeElement;
            document.getElementById('todo-confirm-title').textContent = title;
            document.getElementById('todo-confirm-message').textContent = message;
            document.getElementById('todo-confirm-submit').textContent = confirmLabel;
            modal.setAttribute('aria-hidden', 'false');
            modal.style.display = 'flex';
            document.getElementById('todo-confirm-cancel').focus();
        });
    }

    function resolveTodoAction(confirmed, restoreFocus = true) {
        if (!todoConfirmResolver) {
            return;
        }

        const resolve = todoConfirmResolver;
        const trigger = todoConfirmTrigger;
        const modal = document.getElementById('todo-confirm-modal');
        todoConfirmResolver = null;
        todoConfirmTrigger = null;
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        resolve(confirmed);

        if (!confirmed && restoreFocus && trigger?.isConnected) {
            trigger.focus();
        }
    }

    function closeTodoListMenus(returnFocus = false) {
        document.querySelectorAll('.board-list-menu-toggle[aria-expanded="true"]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
            button.nextElementSibling.hidden = true;

            if (returnFocus) {
                button.focus();
            }
        });
    }

    function closeTodoProjectMenus(returnFocus = false) {
        document.querySelectorAll('.project-menu-toggle[aria-expanded="true"]').forEach((button) => {
            button.setAttribute('aria-expanded', 'false');
            button.nextElementSibling.hidden = true;

            if (returnFocus) {
                button.focus();
            }
        });
    }

    function toggleTodoListMenu(event, button) {
        event.stopPropagation();
        const shouldOpen = button.getAttribute('aria-expanded') !== 'true';
        closeTodoListMenus();

        if (shouldOpen) {
            button.setAttribute('aria-expanded', 'true');
            button.nextElementSibling.hidden = false;
        }
    }

    function toggleTodoProjectMenu(event, button) {
        event.stopPropagation();
        const shouldOpen = button.getAttribute('aria-expanded') !== 'true';
        closeTodoProjectMenus();
        closeTodoListMenus();

        if (shouldOpen) {
            button.setAttribute('aria-expanded', 'true');
            button.nextElementSibling.hidden = false;
        }
    }

    let todoSessionReloading = false;
    const todoLoginPath = new URL("{{ route('login') }}", window.location.origin).pathname;

    function handleExpiredTodoSession(response) {
        const responsePath = response.url ? new URL(response.url, window.location.href).pathname : '';
        const wasRedirectedToLogin = response.redirected && responsePath === todoLoginPath;

        if (![401, 419].includes(response.status) && !wasRedirectedToLogin) {
            return;
        }

        if (!todoSessionReloading) {
            todoSessionReloading = true;
            window.location.reload();
        }

        throw new Error('Sesi login berakhir. Halaman sedang dimuat ulang.');
    }

    async function todoFetch(url, options) {
        const response = await fetch(url, options);
        handleExpiredTodoSession(response);

        return response;
    }

    async function todoRequest(url, options, { showProgress = true } = {}) {
        if (showProgress) {
            setTodoLoading(true);
            await waitForTodoLoadingPaint();
        }

        try {
            const response = await todoFetch(url, options);

            if (response.ok) {
                return response;
            }

            const payload = await response.json().catch(() => ({}));
            const message = payload.message || Object.values(payload.errors || {}).flat()[0] || 'Perubahan tidak dapat disimpan.';
            throw new Error(message);
        } finally {
            if (showProgress) {
                setTodoLoading(false);
            }
        }
    }

    function createTodoBoardSkeleton() {
        const columns = Array.from({ length: 4 }, (_, columnIndex) => {
            const cards = Array.from({ length: columnIndex === 1 ? 3 : 2 }, (_, cardIndex) => {
                const size = cardIndex === 0 ? ' tall' : cardIndex === 1 ? ' short' : '';

                return `<div class="todo-skeleton-block todo-board-skeleton-card${size}"></div>`;
            }).join('');

            return `
                <section class="todo-board-skeleton-column" aria-hidden="true">
                    <div class="todo-skeleton-block todo-board-skeleton-list-title"></div>
                    ${cards}
                </section>`;
        }).join('');

        return `
            <div class="todo-board-skeleton" role="status" aria-label="Loading project">
                <div class="todo-board-skeleton-header" aria-hidden="true">
                    <div class="todo-skeleton-block todo-board-skeleton-title"></div>
                    <div class="todo-skeleton-block todo-board-skeleton-action"></div>
                </div>
                <div class="todo-board-skeleton-columns" aria-hidden="true">${columns}</div>
            </div>`;
    }

    async function refreshTodoShell(url = window.location.href, { pushState = false, replaceState = false } = {}) {
        const refreshId = ++todoShellRefreshId;
        const requestedUrl = new URL(url, window.location.href);
        const shouldShowCardModal = requestedUrl.pathname === todoIndexPath && requestedUrl.searchParams.has('active');
        const requestedProjectId = Number(requestedUrl.searchParams.get('project_id')) || null;
        const shell = document.querySelector('.todo-shell');
        const workspace = document.getElementById('task-workspace-container');
        const boardPane = shell?.querySelector('.pane-list');
        const backdrop = document.getElementById('task-modal-backdrop');
        const openingCardModal = shouldShowCardModal && !shell?.classList.contains('card-modal-open');
        const closingCardModal = !shouldShowCardModal && shell?.classList.contains('card-modal-open');
        const openingProject = requestedUrl.pathname === todoIndexPath
            && requestedProjectId !== null
            && requestedProjectId !== currentProjectId;

        if (openingProject && boardPane) {
            if (!todoBoardSnapshot) {
                todoBoardSnapshot = {
                    pane: boardPane,
                    markup: boardPane.innerHTML,
                    className: boardPane.className,
                    ariaBusy: boardPane.getAttribute('aria-busy'),
                };
            }

            boardPane.classList.remove('project-picker');
            boardPane.setAttribute('aria-busy', 'true');
            boardPane.innerHTML = createTodoBoardSkeleton();
        }

        if (closingCardModal && !todoModalFocusAfterCloseId) {
            todoModalFocusAfterCloseId = activeTodoId;
        }

        if (workspace) {
            if (!todoWorkspaceSnapshot) {
                todoWorkspaceSnapshot = {
                    markup: workspace.innerHTML,
                    className: workspace.className,
                    shellClassName: shell?.className,
                    backdropHidden: backdrop?.hidden ?? true,
                };
            }

            if (shouldShowCardModal) {
                shell?.classList.add('card-modal-open');
                workspace.classList.remove('is-empty');
                workspace.setAttribute('aria-busy', 'true');
                workspace.innerHTML = `
                    <div class="todo-workspace-skeleton" role="status" aria-label="Loading task">
                        <div class="todo-skeleton-block todo-skeleton-meta"></div>
                        <div class="todo-skeleton-block todo-skeleton-title"></div>
                        <div class="todo-skeleton-block todo-skeleton-copy"></div>
                        <div class="todo-skeleton-block todo-skeleton-copy short"></div>
                        <div class="todo-skeleton-block todo-skeleton-section"></div>
                        <div class="todo-skeleton-block todo-skeleton-section"></div>
                    </div>`;
            } else {
                shell?.classList.remove('card-modal-open');
                workspace.classList.add('is-empty');
                workspace.removeAttribute('aria-busy');
            }

            if (backdrop) {
                backdrop.hidden = !shouldShowCardModal;
            }
        }

        if (openingProject) {
            await waitForTodoLoadingPaint();

            if (refreshId !== todoShellRefreshId) {
                return;
            }
        }

        try {
            const response = await todoFetch(url, {
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

            if (refreshId !== todoShellRefreshId) {
                return;
            }

            currentShell.replaceWith(nextShell);
            todoWorkspaceSnapshot = null;
            todoBoardSnapshot = null;

            ['modal-workspace', 'modal-project', 'modal-list', 'modal-task', 'modal-command', 'todo-toast'].forEach((id) => {
                const currentElement = document.getElementById(id);
                const nextElement = nextDocument.getElementById(id);

                if (currentElement && nextElement) {
                    currentElement.replaceWith(nextElement);
                }
            });

            activeTodoId = Number(nextShell.dataset.activeTodoId) || null;
            currentProjectId = Number(nextShell.dataset.projectId) || null;
            currentWorkspaceId = Number(nextShell.dataset.workspaceId) || null;

            if (pushState) {
                window.history.pushState({}, '', url);
            } else if (replaceState) {
                window.history.replaceState({}, '', url);
            }

            bindTodoTitleEditor();
            formatThreadEntryTimes(nextShell);

            if (openingCardModal) {
                document.getElementById('close-task-modal')?.focus();
            } else if (todoModalFocusAfterCloseId && !shouldShowCardModal) {
                document.querySelector(`#task-card-${todoModalFocusAfterCloseId} .task-card-link`)?.focus();
                todoModalFocusAfterCloseId = null;
            }
        } catch (error) {
            if (refreshId === todoShellRefreshId && todoBoardSnapshot?.pane.isConnected) {
                todoBoardSnapshot.pane.innerHTML = todoBoardSnapshot.markup;
                todoBoardSnapshot.pane.className = todoBoardSnapshot.className;

                if (todoBoardSnapshot.ariaBusy === null) {
                    todoBoardSnapshot.pane.removeAttribute('aria-busy');
                } else {
                    todoBoardSnapshot.pane.setAttribute('aria-busy', todoBoardSnapshot.ariaBusy);
                }

                todoBoardSnapshot = null;
            }

            if (refreshId === todoShellRefreshId && workspace?.isConnected) {
                workspace.innerHTML = todoWorkspaceSnapshot?.markup ?? '';
                workspace.className = todoWorkspaceSnapshot?.className ?? 'pane-workspace is-empty';
                workspace.removeAttribute('aria-busy');
                if (shell && todoWorkspaceSnapshot?.shellClassName) {
                    shell.className = todoWorkspaceSnapshot.shellClassName;
                }
                if (backdrop) {
                    backdrop.hidden = todoWorkspaceSnapshot?.backdropHidden ?? true;
                }
                todoWorkspaceSnapshot = null;
            }

            throw error;
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

    function closeTaskModal() {
        if (!document.querySelector('.todo-shell.card-modal-open')) {
            return;
        }

        todoModalFocusAfterCloseId = activeTodoId;
        const url = new URL(window.location.href);
        url.searchParams.delete('active');
        refreshTodoShell(url.href, { replaceState: true }).catch((error) => showToast(error.message));
    }

    function closeModals() {
        resolveTodoAction(false, false);
        document.getElementById('todo-confirm-modal').style.display = 'none';
        document.getElementById('todo-confirm-modal').setAttribute('aria-hidden', 'true');
        document.getElementById('modal-workspace').style.display = 'none';
        document.getElementById('modal-project').style.display = 'none';
        document.getElementById('modal-list').style.display = 'none';
        document.getElementById('modal-task').style.display = 'none';
        document.getElementById('modal-command').style.display = 'none';
    }

    function openNewWorkspaceModal() {
        document.getElementById('workspace-modal-title').textContent = 'New Workspace';
        document.getElementById('workspace-id-input').value = '';
        document.getElementById('workspace-name-input').value = '';
        document.getElementById('workspace-submit-button').textContent = 'Create workspace';
        document.getElementById('modal-workspace').style.display = 'flex';
        document.getElementById('workspace-name-input').focus();
    }

    function openEditWorkspaceModal(button) {
        closeTodoProjectMenus();
        document.getElementById('workspace-modal-title').textContent = 'Rename Workspace';
        document.getElementById('workspace-id-input').value = button.dataset.workspaceId;
        document.getElementById('workspace-name-input').value = button.dataset.workspaceName;
        document.getElementById('workspace-submit-button').textContent = 'Save changes';
        document.getElementById('modal-workspace').style.display = 'flex';
        document.getElementById('workspace-name-input').focus();
        document.getElementById('workspace-name-input').select();
    }

    function openNewProjectModal(workspaceId = currentWorkspaceId) {
        document.getElementById('project-modal-title').textContent = 'New Project';
        document.getElementById('project-id-input').value = '';
        document.getElementById('project-workspace-id').value = String(workspaceId ?? '');
        document.getElementById('project-name-input').value = '';
        document.getElementById('project-color-input').value = '#6366f1';
        document.getElementById('project-submit-button').textContent = 'Create Project';
        document.getElementById('modal-project').style.display = 'flex';
        document.getElementById('project-name-input').focus();
    }

    function openEditProjectModal(button) {
        closeTodoProjectMenus();
        document.getElementById('project-modal-title').textContent = 'Rename Project';
        document.getElementById('project-id-input').value = button.dataset.projectId;
        document.getElementById('project-workspace-id').value = button.dataset.workspaceId;
        document.getElementById('project-name-input').value = button.dataset.projectName;
        document.getElementById('project-color-input').value = button.dataset.projectColor;
        document.getElementById('project-submit-button').textContent = 'Save changes';
        document.getElementById('modal-project').style.display = 'flex';
        document.getElementById('project-name-input').focus();
        document.getElementById('project-name-input').select();
    }

    function toggleWorkspaceProjects(button, workspaceId) {
        const projects = document.getElementById(`workspace-projects-${workspaceId}`);
        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', String(!expanded));
        projects.hidden = expanded;
    }

    function openNewListModal() {
        document.getElementById('modal-list').style.display = 'flex';
        document.getElementById('list-name-input').focus();
    }

    function openNewTaskModal(todoListId = todoSystemListIds.todo) {
        const taskListSelect = document.getElementById('task-list-select');
        const titleInput = document.getElementById('task-title-input');
        const defaultListId = todoListId ?? todoSystemListIds.todo ?? taskListSelect.options[1]?.value ?? '';
        taskListSelect.value = String(defaultListId);
        titleInput.classList.remove('is-invalid');
        titleInput.removeAttribute('aria-invalid');
        document.getElementById('modal-task').style.display = 'flex';
        titleInput.focus();
    }

    function openNewCommandModal(todoId) {
        activeTodoId = todoId;
        document.getElementById('modal-command').style.display = 'flex';
        document.getElementById('command-text-input').focus();
    }

    async function submitNewProject() {
        const name = document.getElementById('project-name-input').value.trim();
        const color = document.getElementById('project-color-input').value;
        const workspace_id = Number(document.getElementById('project-workspace-id').value) || currentWorkspaceId;
        const projectId = Number(document.getElementById('project-id-input').value) || null;
        if (!name) {
            showToast('Nama project wajib diisi.');
            return;
        }

        try {
            const response = await todoRequest(projectId ? `/todos/projects/${projectId}` : "{{ route('todos.projects.store') }}", {
                method: projectId ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ name, color, workspace_id })
            });

            if (projectId) {
                await refreshTodoShell();
            } else {
                const payload = await response.json();
                await refreshTodoShell(`{{ route('todos.index') }}?workspace_id=${workspace_id}&project_id=${payload.project.id}`, { pushState: true });
            }
        } catch (error) {
            showToast(error.message);
        }
    }

    async function deleteTodoProject(button) {
        const projectId = Number(button.dataset.projectId);
        const workspaceId = Number(button.dataset.workspaceId);
        const projectName = button.dataset.projectName;
        closeTodoProjectMenus(true);

        if (Number(button.dataset.projectCardCount) > 0) {
            showToast(`Hapus semua kartu di "${projectName}" sebelum menghapus proyek.`);
            return;
        }

        const confirmed = await confirmTodoAction({
            title: 'Hapus proyek?',
            message: `Proyek "${projectName}" kosong dan akan dihapus.`,
            confirmLabel: 'Hapus proyek',
        });

        if (!confirmed) {
            return;
        }

        try {
            await todoRequest(`/todos/projects/${projectId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken },
            });

            if (currentProjectId === projectId) {
                await refreshTodoShell(`{{ route('todos.index') }}?workspace_id=${workspaceId}`, { pushState: true });
            } else {
                await refreshTodoShell();
            }
        } catch (error) {
            showToast(error.message);
        }
    }

    async function submitNewWorkspace() {
        const name = document.getElementById('workspace-name-input').value.trim();
        const workspaceId = Number(document.getElementById('workspace-id-input').value) || null;

        if (!name) {
            showToast('Nama workspace wajib diisi.');
            document.getElementById('workspace-name-input').focus();
            return;
        }

        try {
            const response = await todoRequest(workspaceId ? `/todos/workspaces/${workspaceId}` : "{{ route('todos.workspaces.store') }}", {
                method: workspaceId ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ name })
            });

            if (workspaceId) {
                await refreshTodoShell();
            } else {
                const payload = await response.json();
                await refreshTodoShell(`{{ route('todos.index') }}?workspace_id=${payload.workspace.id}`, { pushState: true });
            }
        } catch (error) {
            showToast(error.message);
        }
    }

    async function submitNewList() {
        const name = document.getElementById('list-name-input').value.trim();
        const color = document.getElementById('list-color-input').value;

        if (!name) {
            showToast('Nama list wajib diisi.');
            document.getElementById('list-name-input').focus();
            return;
        }

        try {
            await todoRequest("{{ route('todos.lists.store') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ name, color, todo_project_id: currentProjectId })
            });
            await refreshTodoShell();
        } catch (error) {
            showToast(error.message);
        }
    }

    function editTodoListName(event, todoListId) {
        if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
            return;
        }

        event.preventDefault();
        const title = document.getElementById(`todo-list-title-${todoListId}`);
        const input = document.getElementById(`todo-list-title-input-${todoListId}`);

        if (!title || !input || input.dataset.saving === 'true') {
            return;
        }

        input.dataset.originalName = title.textContent.trim();
        input.value = input.dataset.originalName;
        title.hidden = true;
        input.style.display = 'block';
        input.focus();
        input.select();
    }

    function handleTodoListNameKeydown(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            event.currentTarget.blur();
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            const input = event.currentTarget;
            input.value = input.dataset.originalName || '';
            input.dataset.cancelled = 'true';
            input.blur();
        }
    }

    async function saveTodoListName(todoListId) {
        const title = document.getElementById(`todo-list-title-${todoListId}`);
        const input = document.getElementById(`todo-list-title-input-${todoListId}`);

        if (!title || !input || input.dataset.saving === 'true') {
            return;
        }

        const originalName = input.dataset.originalName || title.textContent.trim();
        const nextName = input.value.trim();
        const wasCancelled = input.dataset.cancelled === 'true';
        delete input.dataset.cancelled;
        title.hidden = false;
        input.style.display = 'none';

        if (wasCancelled || !nextName || nextName === originalName) {
            input.value = originalName;
            return;
        }

        input.dataset.saving = 'true';
        title.textContent = nextName;

        try {
            const response = await todoFetch(`/todos/lists/${todoListId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ name: nextName })
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = payload.message || Object.values(payload.errors || {}).flat()[0] || 'List name could not be saved.';
                throw new Error(message);
            }

            title.textContent = payload.list.name;
            title.setAttribute('aria-label', `Edit ${payload.list.name} list name`);
            input.value = payload.list.name;
        } catch (error) {
            title.textContent = originalName;
            title.setAttribute('aria-label', `Edit ${originalName} list name`);
            input.value = originalName;
            showToast(error.message);
        } finally {
            delete input.dataset.saving;
        }
    }

    async function deleteTodoList(todoListId) {
        closeTodoListMenus(true);

        const confirmed = await confirmTodoAction({
            title: 'Hapus daftar?',
            message: 'Daftar kosong ini akan dihapus dan tidak dapat digunakan lagi.',
            confirmLabel: 'Hapus daftar',
        });

        if (!confirmed) {
            return;
        }

        try {
            await todoRequest(`/todos/lists/${todoListId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            await refreshTodoShell();
        } catch (error) {
            showToast(error.message);
        }
    }

    async function submitNewTask() {
        const title = document.getElementById('task-title-input').value.trim();
        const description = document.getElementById('task-desc-input').value.trim();
        const todo_project_id = currentProjectId;
        const priority = document.getElementById('task-priority-select').value;
        const selectedListId = document.getElementById('task-list-select').value;
        const todo_list_id = selectedListId ? Number(selectedListId) : null;
        if (!title) {
            const titleInput = document.getElementById('task-title-input');
            titleInput.classList.add('is-invalid');
            titleInput.setAttribute('aria-invalid', 'true');
            titleInput.focus();
            showToast('Judul task wajib diisi.');
            return;
        }

        document.getElementById('task-title-input').classList.remove('is-invalid');
        document.getElementById('task-title-input').removeAttribute('aria-invalid');

        try {
            const res = await todoRequest("{{ route('todos.store') }}", {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ title, description, todo_project_id, priority, todo_list_id })
            });
            const data = await res.json();
            await refreshTodoShell("{{ route('todos.index', request()->only(['workspace_id', 'project_id', 'status'])) }}", { pushState: true });
        } catch (error) {
            showToast(error.message);
        }
    }

    async function toggleTaskDone(todoId, isChecked) {
        const card = document.getElementById(`task-card-${todoId}`);

        if (!card) {
            return;
        }

        const previousStatus = card.dataset.status;
        const status = isChecked ? 'done' : 'todo';
        const completionSequence = Number(card.dataset.completionSequence || 0) + 1;

        card.dataset.completionSequence = String(completionSequence);
        card.dataset.status = status;
        card.classList.toggle('done', isChecked);
        updateProjectPendingCount(card.dataset.projectId, previousStatus === 'done', isChecked);

        try {
            const todo = await queueTodoCardUpdate(card, { status });

            if (Number(card.dataset.completionSequence) === completionSequence && todo.status !== status) {
                card.dataset.status = todo.status;
                card.classList.toggle('done', todo.status === 'done');
                const checkbox = card.querySelector('.task-checkbox');

                if (checkbox) {
                    checkbox.checked = todo.status === 'done';
                }

                updateProjectPendingCount(card.dataset.projectId, isChecked, todo.status === 'done');
            }
        } catch (error) {
            if (Number(card.dataset.completionSequence) === completionSequence) {
                const persistedStatus = card.dataset.persistedStatus || previousStatus;
                card.dataset.status = persistedStatus;
                card.classList.toggle('done', persistedStatus === 'done');
                const checkbox = card.querySelector('.task-checkbox');

                if (checkbox) {
                    checkbox.checked = persistedStatus === 'done';
                }

                updateProjectPendingCount(card.dataset.projectId, isChecked, persistedStatus === 'done');
                showToast(error.message);
            }
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

    function startTodoDrag(event, todoId) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(todoId));
        event.currentTarget.classList.add('dragging');
    }

    function endTodoDrag(event) {
        event.currentTarget.classList.remove('dragging');
        document.querySelectorAll('.todo-drop-placeholder').forEach((placeholder) => placeholder.remove());
        document.querySelectorAll('.board-column.drag-over').forEach((column) => column.classList.remove('drag-over'));
    }

    function allowTodoDrop(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';

        const column = event.currentTarget;
        const draggedCard = document.querySelector('.board-task-card.dragging');

        if (draggedCard && draggedCard.dataset.listId === column.dataset.listId) {
            column.classList.remove('drag-over');
            updateTodoDropPlaceholder(event, column, draggedCard);
            return;
        }

        document.querySelectorAll('.todo-drop-placeholder').forEach((placeholder) => placeholder.remove());
        column.classList.add('drag-over');
    }

    function updateTodoDropPlaceholder(event, column, draggedCard) {
        const cardsContainer = column.querySelector('.board-column-cards');
        let placeholder = document.querySelector('.todo-drop-placeholder');

        if (!placeholder) {
            placeholder = document.createElement('div');
            placeholder.className = 'todo-drop-placeholder';
            placeholder.setAttribute('aria-hidden', 'true');
        }

        const candidateCards = Array.from(cardsContainer.querySelectorAll('.board-task-card'))
            .filter((taskCard) => taskCard !== draggedCard);
        const targetCard = candidateCards.find((taskCard) => {
            const bounds = taskCard.getBoundingClientRect();

            return event.clientY < bounds.top + (bounds.height / 2);
        });
        const containerBounds = cardsContainer.getBoundingClientRect();
        const targetTop = targetCard
            ? targetCard.getBoundingClientRect().top - containerBounds.top + cardsContainer.scrollTop - 2
            : candidateCards.length > 0
                ? candidateCards[candidateCards.length - 1].getBoundingClientRect().bottom - containerBounds.top + cardsContainer.scrollTop + 2
                : 9;

        placeholder.dataset.beforeTodoId = targetCard?.dataset.todoId || '';
        placeholder.style.top = `${Math.max(0, targetTop)}px`;

        if (placeholder.parentElement !== cardsContainer) {
            cardsContainer.append(placeholder);
        }
    }

    function leaveTodoDrop(event) {
        if (!event.currentTarget.contains(event.relatedTarget)) {
            event.currentTarget.classList.remove('drag-over');
        }
    }

    async function dropTodoCard(event, todoListId) {
        event.preventDefault();
        const column = event.currentTarget;
        column.classList.remove('drag-over');
        const todoId = Number(event.dataTransfer.getData('text/plain'));
        const card = document.getElementById(`task-card-${todoId}`);

        if (!todoId || !card) {
            return;
        }

        const sourceListId = card.dataset.listId === '' ? null : Number(card.dataset.listId);

        if (sourceListId === todoListId) {
            await reorderTodoCard(card, event, todoListId);
            return;
        }

        await moveTodoCard(card, todoListId);
    }

    async function reorderTodoCard(card, event, todoListId) {
        const column = event.currentTarget;
        const cardsContainer = column.querySelector('.board-column-cards');
        const originalOrder = Array.from(cardsContainer.querySelectorAll('.board-task-card'));
        const placeholder = cardsContainer.querySelector('.todo-drop-placeholder');

        if (placeholder) {
            const targetTodoId = Number(placeholder.dataset.beforeTodoId);
            const targetCard = targetTodoId ? document.getElementById(`task-card-${targetTodoId}`) : null;
            cardsContainer.insertBefore(card, targetCard);
            placeholder.remove();
        } else {
            const targetCard = event.target.closest('.board-task-card');

            if (targetCard === card) {
                return;
            }

            if (targetCard) {
                const targetBounds = targetCard.getBoundingClientRect();
                const insertBeforeTarget = event.clientY < targetBounds.top + (targetBounds.height / 2);
                cardsContainer.insertBefore(card, insertBeforeTarget ? targetCard : targetCard.nextElementSibling);
            } else {
                cardsContainer.append(card);
            }
        }

        const todoIds = Array.from(cardsContainer.querySelectorAll('.board-task-card'))
            .map((taskCard) => Number(taskCard.dataset.todoId));

        if (todoIds.every((todoId, index) => todoId === Number(originalOrder[index]?.dataset.todoId))) {
            return;
        }

        card.classList.remove('reorder-confirmed');
        void card.offsetWidth;
        card.classList.add('reorder-confirmed');

        const reorderUrl = "{{ route('todos.cards.reorder') }}";

        try {
            await todoRequest(reorderUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    todo_ids: todoIds,
                    todo_list_id: todoListId,
                    project_id: currentProjectId,
                    status: currentTodoStatusFilter,
                }),
            });
        } catch (error) {
            originalOrder.forEach((taskCard) => cardsContainer.append(taskCard));
            card.classList.remove('reorder-confirmed');
            showToast(error.message);
        }
    }

    function moveTodoCardBetweenColumns(card, todoListId) {
        const sourceColumn = card.closest('.board-column');
        const destinationListId = todoListId === null ? '' : String(todoListId);
        const destinationColumn = document.querySelector(`.board-column[data-list-id="${destinationListId}"]`);
        const currentListId = card.dataset.listId === '' ? null : Number(card.dataset.listId);

        if (!sourceColumn || !destinationColumn || currentListId === todoListId) {
            return;
        }

        const sourceCards = sourceColumn.querySelector('.board-column-cards');
        const destinationCards = destinationColumn.querySelector('.board-column-cards');
        const sourceCount = sourceColumn.querySelector('.board-column-count');
        const destinationCount = destinationColumn.querySelector('.board-column-count');

        destinationCards.querySelector('.board-column-empty')?.remove();
        destinationCards.append(card);
        sourceCount.textContent = String(Math.max(0, Number(sourceCount.textContent) - 1));
        destinationCount.textContent = String(Number(destinationCount.textContent) + 1);
        card.dataset.listId = destinationListId;

        if (!sourceCards.querySelector('.board-task-card')) {
            const emptyState = document.createElement('div');
            emptyState.className = 'board-column-empty';
            emptyState.textContent = 'Drop a card here';
            sourceCards.append(emptyState);
        }

        updateTodoListDeleteState(sourceColumn);
        updateTodoListDeleteState(destinationColumn);
    }

    function updateTodoListDeleteState(column) {
        const deleteButton = column?.querySelector('.board-list-menu-item');

        if (!deleteButton) {
            return;
        }

        const hasCards = column.querySelector('.board-task-card') !== null;
        deleteButton.disabled = hasCards;

        if (hasCards) {
            deleteButton.title = 'Move or delete all cards first';
        } else {
            deleteButton.removeAttribute('title');
        }
    }

    async function moveTodoCard(card, todoListId) {
        const previousListId = card.dataset.listId === '' ? null : Number(card.dataset.listId);
        const moveSequence = Number(card.dataset.moveSequence || 0) + 1;

        card.dataset.moveSequence = String(moveSequence);
        moveTodoCardBetweenColumns(card, todoListId);

        try {
            await queueTodoCardUpdate(card, { todo_list_id: todoListId });
        } catch (error) {
            if (Number(card.dataset.moveSequence) === moveSequence) {
                const persistedListId = card.dataset.persistedListId === ''
                    ? null
                    : Number(card.dataset.persistedListId || previousListId);
                moveTodoCardBetweenColumns(card, persistedListId);
                showToast(error.message);
            }
        }
    }

    function queueTodoCardUpdate(card, changes) {
        const todoId = Number(card.dataset.todoId);
        const previousSave = card.todoSaveQueue || Promise.resolve();
        const currentSave = previousSave.catch(() => {}).then(async () => {
            const response = await todoFetch(`/todos/${todoId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify(changes)
            });
            const payload = await response.json().catch(() => ({}));

            if (!response.ok) {
                const message = payload.message || Object.values(payload.errors || {}).flat()[0] || 'Perubahan tidak dapat disimpan.';
                throw new Error(message);
            }

            card.dataset.persistedStatus = payload.todo?.status || card.dataset.persistedStatus;

            if (payload.todo && Object.prototype.hasOwnProperty.call(payload.todo, 'todo_list_id')) {
                card.dataset.persistedListId = payload.todo.todo_list_id === null ? '' : String(payload.todo.todo_list_id);
            }

            return payload.todo;
        });

        card.todoSaveQueue = currentSave;

        return currentSave;
    }

    async function updateTaskList(todoId, todoListId) {
        try {
            await todoRequest(`/todos/${todoId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ todo_list_id: todoListId ? Number(todoListId) : null })
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

        const todoId = activeTodoId;
        const previousTitle = title.textContent.trim();
        const taskCardTitle = document.querySelector(`#task-card-${todoId} .task-title-label`);

        title.textContent = nextTitle;
        taskCardTitle?.replaceChildren(document.createTextNode(nextTitle));
        cancelTaskTitleEdit();

        try {
            const response = await todoRequest(`/todos/${todoId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ title: nextTitle })
            }, { showProgress: false });
            const data = await response.json();

            if (activeTodoId === todoId) {
                title.textContent = data.todo.title;
            }

            taskCardTitle?.replaceChildren(document.createTextNode(data.todo.title));
        } catch (error) {
            if (activeTodoId === todoId) {
                title.textContent = previousTitle;
                input.value = previousTitle;
            }

            taskCardTitle?.replaceChildren(document.createTextNode(previousTitle));
            showToast(error.message);
        }
    }

    function formatThreadEntryTimes(container = document) {
        const formatter = new Intl.DateTimeFormat(undefined, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });

        container.querySelectorAll('.thread-entry-local-time[datetime]').forEach((timeElement) => {
            const timestamp = new Date(timeElement.dateTime);

            if (!Number.isNaN(timestamp.getTime())) {
                timeElement.textContent = formatter.format(timestamp);
            }
        });
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
    formatThreadEntryTimes();

    document.addEventListener('input', (event) => {
        if (event.target.id === 'task-title-input' && event.target.value.trim()) {
            event.target.classList.remove('is-invalid');
            event.target.removeAttribute('aria-invalid');
        }
    });

    async function deleteActiveTask(todoId) {
        const taskTitle = document.getElementById('ws-task-title')?.textContent.trim();
        const confirmed = await confirmTodoAction({
            title: 'Hapus task?',
            message: taskTitle
                ? `Task "${taskTitle}" dan item terkait akan dihapus permanen.`
                : 'Task dan item terkait akan dihapus permanen.',
            confirmLabel: 'Hapus task',
        });

        if (!confirmed) {
            return;
        }

        try {
            await todoRequest(`/todos/${todoId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            window.location.assign("{{ route('todos.index', request()->only(['workspace_id', 'project_id', 'status'])) }}");
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

    function editThreadNote(itemId) {
        const content = document.getElementById(`thread-content-${itemId}`);
        const editor = document.getElementById(`thread-edit-wrap-${itemId}`);
        const input = document.getElementById(`thread-edit-input-${itemId}`);

        if (!content || !editor || !input) {
            return;
        }

        input.value = content.textContent.trim();
        content.hidden = true;
        editor.hidden = false;
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    }

    function cancelThreadNoteEdit(itemId) {
        const content = document.getElementById(`thread-content-${itemId}`);
        const editor = document.getElementById(`thread-edit-wrap-${itemId}`);
        const input = document.getElementById(`thread-edit-input-${itemId}`);

        if (!content || !editor || !input) {
            return;
        }

        input.value = content.textContent.trim();
        content.hidden = false;
        editor.hidden = true;
    }

    function handleThreadNoteKeydown(event, itemId) {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            cancelThreadNoteEdit(itemId);
        }
    }

    async function saveThreadNote(itemId) {
        const content = document.getElementById(`thread-content-${itemId}`);
        const editor = document.getElementById(`thread-edit-wrap-${itemId}`);
        const input = document.getElementById(`thread-edit-input-${itemId}`);

        if (!content || !editor || !input) {
            return;
        }

        const nextContent = input.value.trim();
        const previousContent = content.textContent.trim();

        if (!nextContent) {
            showToast('Komentar tidak boleh kosong.');
            input.focus();
            return;
        }

        content.textContent = nextContent;
        content.hidden = false;
        editor.hidden = true;

        try {
            const response = await todoRequest(`/todos/items/${itemId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ content: nextContent })
            }, { showProgress: false });
            const data = await response.json();

            content.textContent = data.item.content;
            input.value = data.item.content;
        } catch (error) {
            content.textContent = previousContent;
            content.hidden = true;
            editor.hidden = false;
            input.value = nextContent;
            showToast(error.message);
        }
    }

    async function deleteItem(itemId) {
        const confirmed = await confirmTodoAction({
            title: 'Hapus item?',
            message: 'Item ini akan dihapus permanen.',
            confirmLabel: 'Hapus item',
        });

        if (!confirmed) {
            return;
        }

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
            if (todoConfirmResolver) {
                event.preventDefault();
                resolveTodoAction(false);
                return;
            }

            closeModals();
            closeTodoListMenus(true);
            closeTodoProjectMenus(true);

            return;
        }

        if (event.key === 'Tab' && todoConfirmResolver) {
            const modal = document.getElementById('todo-confirm-modal');
            const cancelButton = document.getElementById('todo-confirm-cancel');
            const submitButton = document.getElementById('todo-confirm-submit');

            if (event.shiftKey && (document.activeElement === cancelButton || !modal.contains(document.activeElement))) {
                event.preventDefault();
                submitButton.focus();
            } else if (!event.shiftKey && (document.activeElement === submitButton || !modal.contains(document.activeElement))) {
                event.preventDefault();
                cancelButton.focus();
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (!event.target.closest('.board-column-menu')) {
            closeTodoListMenus();
        }

        if (!event.target.closest('.project-actions')) {
            closeTodoProjectMenus();
        }

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

    document.addEventListener('keydown', (event) => {
        const dialog = document.getElementById('task-workspace-container');

        if (!dialog || !document.querySelector('.todo-shell.card-modal-open') || event.target.closest?.('.todo-modal-overlay')) {
            return;
        }

        if (event.key === 'Escape') {
            if (event.target.id !== 'ws-task-title-input') {
                closeTaskModal();
            }

            return;
        }

        if (event.key === 'Tab') {
            const focusableElements = [...dialog.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')];
            const firstElement = focusableElements[0];
            const lastElement = focusableElements.at(-1);

            if (event.shiftKey && (document.activeElement === firstElement || !dialog.contains(document.activeElement))) {
                event.preventDefault();
                lastElement?.focus();
            } else if (!event.shiftKey && (document.activeElement === lastElement || !dialog.contains(document.activeElement))) {
                event.preventDefault();
                firstElement?.focus();
            }
        }
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
