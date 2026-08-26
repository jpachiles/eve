<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __invoke(Request $request, ChatService $chat)
    {
        return $chat->send(
            $request->message,
            $request->conversation_id ? (int) $request->conversation_id : null
        );
    }

    public function stream(Request $request, ChatService $chat)
    {
        return $chat->stream(
            $request->message,
            $request->conversation_id
                ? (int) $request->conversation_id
                : null
        );
    }

}
