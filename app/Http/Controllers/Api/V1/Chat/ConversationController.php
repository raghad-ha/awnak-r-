<?php

namespace App\Http\Controllers\Api\V1\Chat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Models\Organization;
use App\Models\Message;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Unauthenticated.');

        $q = Conversation::query()->with(['application.opportunity']);

        if ($user->type === 'volunteer') {
            $q->where('volunteer_user_id', $user->id);
        } elseif ($user->type === 'organization') {
            $orgId = Organization::where('user_id', $user->id)->value('id');
            abort_if(!$orgId, 422, 'Organization profile not found.');
            $q->where('organization_id', $orgId);
        } else {
            abort(403, 'Staff cannot access conversations.');
        }

        $items = $q->latest('id')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }

    // Optional: mark all messages read for this user
    public function markRead(Request $request, $id)
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Unauthenticated.');

        $conv = Conversation::findOrFail($id);

        // ensure participant
        $isVolunteer = ($conv->volunteer_user_id === $user->id);
        $isOrg = false;

        if ($user->type === 'organization') {
            $orgId = Organization::where('user_id', $user->id)->value('id');
            $isOrg = ($orgId && $conv->organization_id === $orgId);
        }

        abort_unless($isVolunteer || $isOrg, 403, 'Not a participant.');

        // mark messages from "other side" as read
        Message::where('conversation_id', $conv->id)
            ->whereNull('read_at')
            ->where('sender_user_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Marked as read.',
            'data' => null,
        ]);
    }
}