<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\NoteMessageController;
use App\Http\Controllers\ShareController;
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
Route::middleware('auth')->group(function () {
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
});
