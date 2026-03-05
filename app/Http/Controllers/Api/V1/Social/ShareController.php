<?php

namespace App\Http\Controllers\Api\V1\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Post;
use App\Models\PostShare;

class ShareController extends Controller
{
    public function store(Request $request, $id)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $original = Post::findOrFail($id);

        $data = $request->validate([
            'comment' => ['nullable','string','max:2000'],
        ]);

        $share = DB::transaction(function () use ($user, $original, $data) {
            return PostShare::create([
                'original_post_id' => $original->id,
                'shared_by_user_id' => $user->id,
                'comment' => $data['comment'] ?? null,
            ]);
        });

        return response()->json(['success'=>true,'message'=>'Shared.','data'=>['share'=>$share]], 201);
    }
}