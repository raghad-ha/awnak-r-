<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Opportunity;
use App\Models\Post;

class OrganizationController extends Controller
{
    public function index(Request $request)
    {
        $q = Organization::query();

        if ($request->filled('city')) {
            $q->where('city', 'like', '%' . $request->city . '%');
        }

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $q->where(function ($sub) use ($keyword) {
                $sub->where('org_name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $items = $q->latest('id')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }

    public function show($id)
    {
        $organization = Organization::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'organization' => $organization,
            ],
        ]);
    }

    public function opportunities(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $items = Opportunity::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'approved')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }

    public function posts(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);

        $items = Post::with(['media', 'tags'])
            ->where('visibility', 'public')
            ->where(function ($q) use ($organization) {
                $q->whereHas('tags', function ($t) use ($organization) {
                    $t->where('taggable_type', \App\Models\Organization::class)
                      ->where('taggable_id', $organization->id);
                });
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