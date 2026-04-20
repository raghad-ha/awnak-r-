<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Story;

class FeedController extends Controller
{
    public function posts(Request $request)
    {
        $q = Post::with(['media', 'tags'])
            ->where('visibility', 'public')
            ->latest('id');

        if ($request->filled('keyword')) {
            $q->where('body', 'like', '%' . $request->keyword . '%');
        }

        $items = $q->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }

    public function stories(Request $request)
    {
        $items = Story::with('media')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }
}