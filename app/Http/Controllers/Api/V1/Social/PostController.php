<?php

namespace App\Http\Controllers\Api\V1\Social;

use App\Models\PostTag;
use App\Models\User;
use App\Models\Organization;
use App\Models\Opportunity;
use App\Models\Application;
use App\Notifications\TaggedInPostNotification;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\PostMedia;


class PostController extends Controller
{
public function bulkTagByOpportunity(Request $request, $id)
{
    $user = $request->user();
    abort_if($user->type !== 'organization', 403, 'Only organizations can bulk tag.');

    $post = \App\Models\Post::findOrFail($id);
    abort_if($post->author_user_id !== $user->id, 403, 'Only author can bulk tag.');

    $data = $request->validate([
        'opportunity_id' => ['required','integer','exists:opportunities,id'],
    ]);

    $orgId = Organization::where('user_id', $user->id)->value('id');
    abort_if(!$orgId, 422, 'Organization profile not found.');

    $opp = Opportunity::findOrFail($data['opportunity_id']);
    abort_if($opp->organization_id !== $orgId, 403, 'Not your opportunity.');

    $volunteerIds = Application::where('opportunity_id', $opp->id)
        ->where('status', 'accepted')
        ->pluck('volunteer_user_id')
        ->unique();

    $created = [];

    DB::transaction(function () use ($volunteerIds, $post, $user, &$created) {
        foreach ($volunteerIds as $vid) {
            $exists = PostTag::where('post_id',$post->id)
                ->where('taggable_type', User::class)
                ->where('taggable_id', $vid)
                ->exists();

            if (!$exists) {
                $created[] = PostTag::create([
                    'post_id' => $post->id,
                    'taggable_type' => User::class,
                    'taggable_id' => $vid,
                    'tagged_by_user_id' => $user->id,
                ]);
            }
        }
    });

    foreach ($created as $tag) {
        $target = User::find($tag->taggable_id);
        if ($target) {
            $target->notify(new TaggedInPostNotification([
                'type' => 'post_tag',
                'post_id' => $post->id,
                'tagged_by_user_id' => $user->id,
                'message' => 'You were tagged in a post.',
                'opportunity_id' => $opp->id,
            ]));
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Bulk tagging completed.',
        'data' => [
            'tagged_count' => count($created),
            'volunteer_ids' => $volunteerIds,
        ],
    ]);
}
public function syncTags(Request $request, $id)
{
    $user = $request->user();

    $post = \App\Models\Post::findOrFail($id);
    abort_if($post->author_user_id !== $user->id, 403, 'Only author can edit tags.');

    $data = $request->validate([
        'tags' => ['required','array'],
        'tags.*.type' => ['required','in:volunteer,organization'],
        'tags.*.id' => ['required','integer'],
    ]);

    // get org_id if current user is organization
    $orgId = null;
    if ($user->type === 'organization') {
        $orgId = Organization::where('user_id', $user->id)->value('id');
    }

    $newTags = [];

    DB::transaction(function () use ($data, $post, $user, $orgId, &$newTags) {

        // delete old tags and replace (simpler than diff)
        PostTag::where('post_id', $post->id)->delete();

        foreach ($data['tags'] as $t) {
            if ($t['type'] === 'organization') {
                abort_if(!Organization::where('id',$t['id'])->exists(), 422, 'Organization not found.');
                $taggableType = Organization::class;
            } else {
                $target = User::find($t['id']);
                abort_if(!$target, 422, 'User not found.');
                abort_if($target->type !== 'volunteer', 422, 'Target must be volunteer.');
                $taggableType = User::class;

                // Rule: org can tag only accepted volunteers
                if ($user->type === 'organization') {
                    abort_if(!$orgId, 422, 'Organization profile not found.');

                    $allowed = Application::query()
                        ->join('opportunities','opportunities.id','=','applications.opportunity_id')
                        ->where('opportunities.organization_id', $orgId)
                        ->where('applications.volunteer_user_id', $t['id'])
                        ->where('applications.status', 'accepted')
                        ->exists();

                    abort_unless($allowed, 403, 'Org can tag only accepted volunteers.');
                }
            }

            $tag = PostTag::create([
                'post_id' => $post->id,
                'taggable_type' => $taggableType,
                'taggable_id' => $t['id'],
                'tagged_by_user_id' => $user->id,
            ]);

            $newTags[] = $tag;
        }
    });

    // Send notifications for new tags
    foreach ($newTags as $tag) {
        if ($tag->taggable_type === User::class) {
            $target = User::find($tag->taggable_id);
            if ($target) {
                $target->notify(new TaggedInPostNotification([
                    'type' => 'post_tag',
                    'post_id' => $post->id,
                    'tagged_by_user_id' => $user->id,
                    'message' => 'You were tagged in a post.'
                ]));
            }
        }

        if ($tag->taggable_type === Organization::class) {
            $org = Organization::find($tag->taggable_id);
            if ($org) {
                $orgUser = User::find($org->user_id);
                if ($orgUser) {
                    $orgUser->notify(new TaggedInPostNotification([
                        'type' => 'post_tag',
                        'post_id' => $post->id,
                        'tagged_by_user_id' => $user->id,
                        'message' => 'Your organization was tagged in a post.'
                    ]));
                }
            }
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Tags updated.',
        'data' => ['post' => $post->load('tags.taggable')],
    ]);
}
    
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        // Simple feed: public posts only (you can extend to followers later)
        $q = Post::with(['media','tags.taggable'])
    ->where('visibility', 'public')
    ->latest('id');

   $items = $q->paginate(20);

        return response()->json(['success'=>true,'message'=>'','data'=>$items]);
    }

    public function show(Request $request, $id)
    {
       $post = Post::with(['media','tags.taggable'])->findOrFail($id);

   return response()->json([
  'success'=>true,
  'message'=>'',
  'data'=>['post'=>$post]
]);
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
            'data' => ['post' => $post->load(['media','tags.taggable'])],
        ], 201);
    }
    
public function update(Request $request, $id)
{
    $user = $request->user();
    abort_if(!$user, 401);

    $post = Post::findOrFail($id);

    abort_if($post->author_user_id !== $user->id, 403, 'You can only update your own post.');

    $data = $request->validate([
        'body' => ['nullable', 'string', 'max:5000'],
        'visibility' => ['nullable', 'in:public,followers,private'],
    ]);

    $post->update($data);

    return response()->json([
        'success' => true,
        'message' => 'Post updated.',
        'data' => [
            'post' => $post->load(['media', 'tags.taggable']),
        ],
    ]);
}

public function destroy(Request $request, $id)
{
    $user = $request->user();
    abort_if(!$user, 401);

    $post = Post::findOrFail($id);

    abort_if($post->author_user_id !== $user->id && $user->type !== 'staff', 403, 'Not allowed to delete this post.');

    $post->delete();

    return response()->json([
        'success' => true,
        'message' => 'Post deleted.',
        'data' => null,
    ]);
}

public function addMedia(Request $request, $id)
{
    $user = $request->user();
    abort_if(!$user, 401);

    $post = Post::findOrFail($id);

    abort_if($post->author_user_id !== $user->id, 403, 'You can only add media to your own post.');

    $data = $request->validate([
        'file' => ['required', 'file', 'max:20480'],
        'type' => ['nullable', 'in:image,video'],
    ]);

    $file = $request->file('file');
    $type = $data['type'] ?? 'image';
    $path = $file->store('posts', 'public');

    $media = \App\Models\PostMedia::create([
        'post_id' => $post->id,
        'type' => $type,
        'path' => $path,
        'thumbnail_path' => null,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Media uploaded.',
        'data' => [
            'media' => $media,
        ],
    ], 201);
}

public function deleteMedia(Request $request, $id, $mediaId)
{
    $user = $request->user();
    abort_if(!$user, 401);

    $post = \App\Models\Post::findOrFail($id);

    abort_if($post->author_user_id !== $user->id && $user->type !== 'staff', 403, 'Not allowed to delete this media.');

    $media = \App\Models\PostMedia::query()
        ->where('post_id', $post->id)
        ->findOrFail($mediaId);

    $media->delete();

    return response()->json([
        'success' => true,
        'message' => 'Post media deleted.',
        'data' => null,
    ]);
}
}