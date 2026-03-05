<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Unauthenticated.');

        $q = $user->notifications()->latest();

        if ($request->boolean('unread_only')) {
            $q = $user->unreadNotifications()->latest();
        }

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $q->paginate(20),
        ]);
    }

    public function read(Request $request, $id)
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Unauthenticated.');

        $n = $user->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data' => null,
        ]);
    }

    public function readAll(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401, 'Unauthenticated.');

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read.',
            'data' => null,
        ]);
    }
}