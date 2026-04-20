<?php

namespace App\Http\Controllers\Api\V1\Social;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Story;
use App\Models\StoryMedia;

class StoryController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $items = Story::with('media')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->paginate(20);

        return response()->json(['success'=>true,'message'=>'','data'=>$items]);
    }

    // multipart/form-data
    public function store(Request $request)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $data = $request->validate([
            'caption' => ['nullable','string','max:255'],
            'expires_in_hours' => ['nullable','integer','min:1','max:72'],
            'media' => ['required','array','min:1'],
            'media.*' => ['file','max:20480'],
            'media_types' => ['nullable','array'],
            'media_types.*' => ['in:image,video'],
        ]);

        $hours = $data['expires_in_hours'] ?? 24;

        $story = DB::transaction(function () use ($request, $user, $data, $hours) {

            $story = Story::create([
                'author_user_id' => $user->id,
                'caption' => $data['caption'] ?? null,
                'expires_at' => now()->addHours($hours),
            ]);

            $files = $request->file('media', []);
            $types = $data['media_types'] ?? [];

            foreach ($files as $i => $file) {
                $type = $types[$i] ?? 'image';
                $path = $file->store('stories', 'public');

                StoryMedia::create([
                    'story_id' => $story->id,
                    'type' => $type,
                    'path' => $path,
                    'thumbnail_path' => null,
                ]);
            }

            return $story;
        });

        return response()->json([
            'success' => true,
            'message' => 'Story created.',
            'data' => ['story' => $story->load('media')],
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        abort_if(!$user, 401);

        $story = Story::findOrFail($id);
        abort_if($story->author_user_id !== $user->id, 403);

        $story->delete();

        return response()->json(['success'=>true,'message'=>'Story deleted.','data'=>null]);
    }
    public function addMedia(Request $request, $id)
{
    $user = $request->user();
    abort_if(!$user, 401);

    $story = \App\Models\Story::findOrFail($id);

    abort_if($story->author_user_id !== $user->id, 403, 'You can only add media to your own story.');

    $data = $request->validate([
        'file' => ['required', 'file', 'max:20480'],
        'type' => ['nullable', 'in:image,video'],
    ]);

    $file = $request->file('file');
    $type = $data['type'] ?? 'image';
    $path = $file->store('stories', 'public');

    $media = \App\Models\StoryMedia::create([
        'story_id' => $story->id,
        'type' => $type,
        'path' => $path,
        'thumbnail_path' => null,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Story media uploaded.',
        'data' => [
            'media' => $media,
        ],
    ], 201);
}
}