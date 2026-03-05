<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Organization;

class MessageController extends Controller
{
    private function ensureParticipant(Request $request, Conversation $conv): void
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Unauthenticated.');

        $isVolunteer = ($conv->volunteer_user_id === $user->id);
        $isOrg = false;

        if ($user->type === 'organization') {
            $orgId = Organization::where('user_id', $user->id)->value('id');
            $isOrg = ($orgId && $conv->organization_id === $orgId);
        }

        abort_unless($isVolunteer || $isOrg, 403, 'Not a participant.');
    }

    public function index(Request $request, $id)
    {
        $conv = Conversation::findOrFail($id);
        $this->ensureParticipant($request, $conv);

        $items = Message::with('sender')
            ->where('conversation_id', $conv->id)
            ->orderBy('id', 'desc')
            ->paginate(30);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }

    public function store(Request $request, $id)
    {
        $conv = Conversation::findOrFail($id);
        $this->ensureParticipant($request, $conv);

        $data = $request->validate([
            'body' => ['required','string','max:5000'],
        ]);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message sent.',
            'data' => ['message' => $msg],
        ], 201);
    }
}