<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NoteMessageController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;

// Public Shared Note Route (Accessible without login if public)
Route::get('/share/{token}', [ShareController::class, 'show'])->name('notes.share');

// Authentication (Cybeat SSO)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Workspace Protected Routes
Route::middleware(['auth', 'sso.token.active'])->group(function () {
    Route::get('/', [WorkspaceController::class, 'index'])->name('workspace');

    // Folders
    Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
    Route::put('/folders/{folder}', [FolderController::class, 'update'])->name('folders.update');
    Route::delete('/folders/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

    // Notes
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::get('/notes/{note}', [NoteController::class, 'show'])->name('notes.show');
    Route::put('/notes/{note}', [NoteController::class, 'update'])->name('notes.update');
    Route::delete('/notes/{note}', [NoteController::class, 'destroy'])->name('notes.destroy');
    Route::post('/notes/{note}/share-token', [ShareController::class, 'regenerate'])->name('notes.share.regenerate');

    // Note Messages (Markdown Chat Mode)
    Route::post('/notes/{note}/messages', [NoteMessageController::class, 'store'])->name('notes.messages.store');
    Route::put('/notes/{note}/messages/{message}', [NoteMessageController::class, 'update'])->name('notes.messages.update');
    Route::delete('/notes/{note}/messages/{message}', [NoteMessageController::class, 'destroy'])->name('notes.messages.destroy');
    Route::post('/notes/{note}/clear', [NoteMessageController::class, 'clear'])->name('notes.messages.clear');

    // Advanced Linear-Style To-Do
    Route::get('/todos', [TodoController::class, 'index'])->name('todos.index');
    Route::post('/todos/lists', [TodoController::class, 'storeList'])->name('todos.lists.store');
    Route::put('/todos/lists/{todoList}', [TodoController::class, 'updateList'])->name('todos.lists.update');
    Route::put('/todos/lists/{todoList}/cards/reorder', [TodoController::class, 'reorderCards'])->name('todos.lists.reorder');
    Route::delete('/todos/lists/{todoList}', [TodoController::class, 'deleteList'])->name('todos.lists.destroy');
    Route::post('/todos/projects', [TodoController::class, 'storeProject'])->name('todos.projects.store');
    Route::delete('/todos/projects/{project}', [TodoController::class, 'deleteProject'])->name('todos.projects.destroy');
    Route::post('/todos', [TodoController::class, 'store'])->name('todos.store');
    Route::put('/todos/cards/reorder', [TodoController::class, 'reorderCards'])->name('todos.cards.reorder');
    Route::get('/todos/{todo}', [TodoController::class, 'show'])->name('todos.show');
    Route::put('/todos/{todo}', [TodoController::class, 'update'])->name('todos.update');
    Route::delete('/todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('/todos/{todo}/items', [TodoController::class, 'storeItem'])->name('todos.items.store');
    Route::put('/todos/items/{item}', [TodoController::class, 'updateItem'])->name('todos.items.update');
    Route::delete('/todos/items/{item}', [TodoController::class, 'deleteItem'])->name('todos.items.destroy');
});
