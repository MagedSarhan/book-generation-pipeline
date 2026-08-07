<?php

use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AssetTokenController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\FalWebhookController;
use App\Http\Controllers\GenerationController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\PageVersionController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// Health Check Endpoint
Route::get('/health', HealthCheckController::class)->name('health');

// Fal Webhook Receiver (No CSRF)
Route::post('/webhooks/fal/{job_uuid}', [FalWebhookController::class, 'handle'])->name('fal.webhook');

// Temporary Asset Fetch Endpoint for Fal
Route::get('/assets/token/{token}', [AssetTokenController::class, 'show'])->name('asset.token.show');

// Application Direct Asset Preview (Auth Protected)
Route::get('/assets/direct', [AssetTokenController::class, 'direct'])->middleware('auth')->name('asset.direct');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated Creative Platform Workspace
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Projects Wizard & Workspace
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project:uuid}', [ProjectController::class, 'show'])->name('projects.show');
    Route::put('/projects/{project:uuid}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project:uuid}', [DashboardController::class, 'destroy'])->name('projects.destroy');

    // Generation Endpoints
    Route::post('/projects/{project:uuid}/generate', [GenerationController::class, 'generate'])->name('projects.generate');
    Route::get('/projects/{project:uuid}/status', [GenerationController::class, 'projectStatus'])->name('projects.status');
    Route::post('/pages/{page}/regenerate', [GenerationController::class, 'regeneratePage'])->name('pages.regenerate');
    Route::post('/pages/{page}/edit', [GenerationController::class, 'editPage'])->name('pages.edit');
    Route::post('/batches/{batch}/pause', [GenerationController::class, 'pauseBatch'])->name('batches.pause');
    Route::post('/batches/{batch}/resume', [GenerationController::class, 'resumeBatch'])->name('batches.resume');
    Route::post('/jobs/{job}/retry', [GenerationController::class, 'retryJob'])->name('jobs.retry');

    // Version Management
    Route::post('/versions/{version}/select', [PageVersionController::class, 'selectVersion'])->name('versions.select');
    Route::get('/versions/{version}/download', [PageVersionController::class, 'downloadVersion'])->name('versions.download');
    Route::delete('/versions/{version}', [PageVersionController::class, 'destroy'])->name('versions.destroy');

    // Exports
    Route::get('/pages/{page}/download', [ExportController::class, 'downloadPage'])->name('pages.download');
    Route::get('/projects/{project:uuid}/export/zip', [ExportController::class, 'zipExport'])->name('projects.export.zip');

    // Admin Settings
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
});
