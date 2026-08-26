<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConversationController;

Route::post('/chat', ChatController::class);
Route::get('/conversation/{conversation}', ConversationController::class);
Route::post('/chat/stream', [ChatController::class, 'stream']);
Route::get('/conversations', function () {
    return \App\Models\Conversation::latest()
        ->get(['id', 'title']);
});

Route::post('/chat/stream', [ChatController::class, 'stream']);
