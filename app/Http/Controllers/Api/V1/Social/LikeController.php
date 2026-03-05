<?php

namespace App\Http\Controllers\Api\V1\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostLike;

class LikeController extends Controller
{
    public function like(Request $request, $id)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        Post::findOrFail($id);

        PostLike::updateOrCreate([
            'post_id' => $id,
            'user_id' => $user->id,
        ]);

        return response()->json(['success'=>true,'message'=>'Liked.','data'=>null]);
    }

    public function unlike(Request $request, $id)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        PostLike::where('post_id', $id)->where('user_id', $user->id)->delete();

        return response()->json(['success'=>true,'message'=>'Unliked.','data'=>null]);
    }
}