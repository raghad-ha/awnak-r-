<?php

namespace App\Http\Controllers\Api\V1\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Post;
use App\Models\PostMedia;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        // Simple feed: public posts only (you can extend to followers later)
        $q = Post::with(['media','tags'])
            ->where('visibility', 'public')
            ->latest('id');

        $items = $q->paginate(20);

        return response()->json(['success'=>true,'message'=>'','data'=>$items]);
    }

    public function show(Request $request, $id)
    {
        $post = Post::with(['media','tags'])->findOrFail($id);
        return response()->json(['success'=>true,'message'=>'','data'=>['post'=>$post]]);
    }

    // multipart/form-data
    public function store(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $data = $request->validate([
            'body' => ['nullable','string','max:5000'],
            'visibility' => ['required','in:public,followers,private'],

            // media uploads
            'media' => ['nullable','array'],
            'media.*' => ['file','max:20480'], // 20MB each
            'media_types' => ['nullable','array'],
            'media_types.*' => ['in:image,video'],

            // tags (organization ids)
            'tag_organization_ids' => ['nullable','array'],
            'tag_organization_ids.*' => ['integer','exists:organizations,id'],
        ]);

        $post = DB::transaction(function () use ($request, $user, $data) {

            $post = Post::create([
                'author_user_id' => $user->id,
                'body' => $data['body'] ?? null,
                'visibility' => $data['visibility'],
            ]);

            // tags
            if (!empty($data['tag_organization_ids'])) {
    foreach ($data['tag_organization_ids'] as $orgId) {
        \App\Models\PostTag::create([
            'post_id' => $post->id,
            'taggable_type' => \App\Models\Organization::class,
            'taggable_id' => $orgId,
            'tagged_by_user_id' => $user->id,
        ]);
    }
}

            // media
            $files = $request->file('media', []);
            $types = $data['media_types'] ?? [];

            foreach ($files as $i => $file) {
                $type = $types[$i] ?? 'image';
                $path = $file->store('posts', 'public');

                PostMedia::create([
                    'post_id' => $post->id,
                    'type' => $type,
                    'path' => $path,
                    'thumbnail_path' => null,
                ]);
            }

            return $post;
        });

        return response()->json([
            'success' => true,
            'message' => 'Post created.',
            'data' => ['post' => $post->load(['media','tags'])],
        ], 201);
    }
    public function updateTags(Request $request, $id)
{
    $user = $request->user();
    abort_if(!$user, 401);

    $data = $request->validate([
        'tag_organization_ids' => ['nullable', 'array'],
        'tag_organization_ids.*' => ['integer', 'exists:organizations,id'],
    ]);

    $post = Post::findOrFail($id);

    abort_if($post->author_user_id !== $user->id, 403, 'You can only update your own post tags.');

    DB::transaction(function () use ($post, $user, $data) {
        $ids = $data['tag_organization_ids'] ?? [];

        // remove old organization tags only
        $post->tags()
            ->where('taggable_type', \App\Models\Organization::class)
            ->delete();

        // insert new organization tags
        foreach ($ids as $orgId) {
            \App\Models\PostTag::create([
                'post_id' => $post->id,
                'taggable_type' => \App\Models\Organization::class,
                'taggable_id' => $orgId,
                'tagged_by_user_id' => $user->id,
            ]);
        }
    });

    return response()->json([
        'success' => true,
        'message' => 'Post tags updated.',
        'data' => [
            'post' => $post->load(['media', 'tags.taggable']),
        ],
    ]);
}
}