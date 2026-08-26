<?php

namespace App\Http\Controllers;

use App\Models\Conversation;

class ConversationController extends Controller
{
    public function __invoke(Conversation $conversation)
    {
        return $conversation->messages()
            ->orderBy('id')
            ->get(['role', 'content']);
    }
}
