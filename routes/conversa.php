<?php

use Illuminate\Support\Facades\Route;
use VendorName\Conversa\Http\Controllers\ConversaChannelController;
use VendorName\Conversa\Http\Controllers\ConversaMessageController;
use VendorName\Conversa\Http\Controllers\ConversaThreadController;
use VendorName\Conversa\Http\Controllers\ConversaChatController;

Route::prefix('conversa')->middleware(['web', 'auth'])->group(function () {
    // Channel routes
    Route::get('/spaces', [ConversaChannelController::class, 'index'])->name('conversa.spaces.index');
    Route::get('/spaces/{space}', [ConversaChannelController::class, 'show'])->name('conversa.spaces.show');
    Route::post('/spaces', [ConversaChannelController::class, 'store'])->name('conversa.spaces.store');
    Route::put('/spaces/{space}', [ConversaChannelController::class, 'update'])->name('conversa.spaces.update');
    Route::delete('/spaces/{space}', [ConversaChannelController::class, 'destroy'])->name('conversa.spaces.destroy');

    // Message routes
    Route::post('/messages', [ConversaMessageController::class, 'store'])->name('conversa.messages.store');
    Route::get('/messages/{space}', [ConversaMessageController::class, 'index'])->name('conversa.messages.index');

    // Thread routes
    Route::get('/threads', [ConversaThreadController::class, 'index'])->name('conversa.threads.index');
    Route::get('/threads/{thread}', [ConversaThreadController::class, 'show'])->name('conversa.threads.show');
    Route::post('/threads', [ConversaThreadController::class, 'store'])->name('conversa.threads.store');
    Route::put('/threads/{thread}', [ConversaThreadController::class, 'update'])->name('conversa.threads.update');
    Route::delete('/threads/{thread}', [ConversaThreadController::class, 'destroy'])->name('conversa.threads.destroy');

    // Chat routes
    Route::get('/chat', [ConversaChatController::class, 'index'])->name('conversa.chat.index');
});
