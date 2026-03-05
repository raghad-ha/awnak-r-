<?php

namespace App\Http\Controllers\Api\V1\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;

class CommentController extends Controller
{
    public function index(Request $request, $id)
    {
        Post::findOrFail($id);

        // top-level comments (with replies)
        $items = Comment::with('replies')
            ->where('post_id', $id)
            ->whereNull('parent_id')
            ->latest('id')
            ->paginate(20);

        return response()->json(['success'=>true,'message'=>'','data'=>$items]);
    }

    public function store(Request $request, $id)
    {
        $user = $request->user();
        abort_if(!$user, 401);
        Post::findOrFail($id);

        $data = $request->validate([
            'body' => ['required','string','max:2000'],
            'parent_id' => ['nullable','integer','exists:comments,id'],
        ]);

        // if replying, ensure parent belongs to same post
        if (!empty($data['parent_id'])) {
            $parent = Comment::findOrFail($data['parent_id']);
            abort_if($parent->post_id != $id, 422, 'Parent comment not in this post.');
        }

        $comment = Comment::create([
            'post_id' => $id,
            'user_id' => $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => $data['body'],
        ]);

        return response()->json(['success'=>true,'message'=>'Comment added.','data'=>['comment'=>$comment]], 201);
    }
}