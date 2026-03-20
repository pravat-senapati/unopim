<?php

use Illuminate\Support\Facades\Route;
use Webkul\AiAgent\Http\Controllers\AgentController;
use Webkul\AiAgent\Http\Controllers\ChatController;
use Webkul\AiAgent\Http\Controllers\ExecutionController;
use Webkul\AiAgent\Http\Controllers\GenerateController;

// Route middleware: ['admin'] only — NOT ['web', 'admin']
Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {

    Route::prefix('ai-agent')->name('ai-agent.')->group(function () {

        // ── AI Settings (redirects to Magic AI configuration) ─
        Route::get('settings', fn () => redirect()->route('admin.configuration.edit', ['general', 'magic_ai']))
             ->name('settings');

        // ── Agents ───────────────────────────────────────────
        Route::get('agents', [AgentController::class, 'index'])
             ->name('agents.index');

        Route::get('agents/create', [AgentController::class, 'create'])
             ->name('agents.create');

        Route::post('agents', [AgentController::class, 'store'])
             ->name('agents.store');

        Route::get('agents/get', [AgentController::class, 'get'])
             ->name('agents.get');

        Route::get('agents/{id}/edit', [AgentController::class, 'edit'])
             ->name('agents.edit');

        Route::put('agents/{id}', [AgentController::class, 'update'])
             ->name('agents.update');

        Route::delete('agents/{id}', [AgentController::class, 'destroy'])
             ->name('agents.destroy');

        // ── Generate (Image → Product) ─────────────────────
        Route::get('generate', [GenerateController::class, 'index'])
             ->name('generate.index');

        Route::post('generate', [GenerateController::class, 'process'])
             ->name('generate.process');

        // ── Execution ────────────────────────────────────────
        Route::post('execute', [ExecutionController::class, 'execute'])
             ->name('execute');

        // ── Chat Widget ──────────────────────────────────────
        Route::post('chat', [ChatController::class, 'send'])
             ->name('chat.send');

        Route::get('chat/magic-ai-config', [ChatController::class, 'magicAiConfig'])
             ->name('chat.magic-ai-config');
    });

});
