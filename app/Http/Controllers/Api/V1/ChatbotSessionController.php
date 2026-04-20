<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ChatbotSession;
use App\Models\ChatbotMessage;

class ChatbotSessionController extends Controller
{
    public function store(Request $request)
    {
        $session = ChatbotSession::create([
            'user_id' => optional($request->user())->id,
            'guest_token' => $request->user() ? null : Str::uuid()->toString(),
            'title' => 'New Chat',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Session created.',
            'data' => [
                'session' => $session,
            ],
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $session = ChatbotSession::with('messages')->findOrFail($id);

        if ($session->user_id) {
            abort_if(optional($request->user())->id !== $session->user_id, 403, 'Not allowed.');
        }

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'session' => $session,
            ],
        ]);
    }

    public function message(Request $request, $id)
    {
        $session = ChatbotSession::findOrFail($id);

        if ($session->user_id) {
            abort_if(optional($request->user())->id !== $session->user_id, 403, 'Not allowed.');
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $userMsg = ChatbotMessage::create([
            'chatbot_session_id' => $session->id,
            'sender' => 'user',
            'body' => $data['body'],
        ]);

        // temporary fake bot reply
        $botReply = ChatbotMessage::create([
            'chatbot_session_id' => $session->id,
            'sender' => 'bot',
            'body' => 'This is a temporary chatbot reply.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message stored.',
            'data' => [
                'user_message' => $userMsg,
                'bot_message' => $botReply,
            ],
        ], 201);
    }
}