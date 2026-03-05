<?php

namespace App\Http\Controllers\Api\V1\Volunteer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\OrganizationReview;

class OrganizationReviewController extends Controller
{
    public function store(Request $request, $id)
    {
        $user = $request->user();

        if ($user->status !== 'approved' || $user->type !== 'volunteer') {
            abort(403, 'Only approved volunteers can review organizations.');
        }

        $data = $request->validate([
            'commitment'   => ['required','integer','min:1','max:5'],
            'task_clarity' => ['required','integer','min:1','max:5'],
            'work_env'     => ['required','integer','min:1','max:5'],
            'time_respect' => ['required','integer','min:1','max:5'],
            'comment'      => ['nullable','string','max:5000'],
        ]);

        $app = Application::with('opportunity')->findOrFail($id);

        // must be the same volunteer
        abort_if($app->volunteer_user_id !== $user->id, 403, 'Not your application.');

        // only after accepted (you can change to "completed" if you add that later)
        abort_if($app->status !== 'accepted', 422, 'You can review only accepted applications.');

        $orgId = $app->opportunity->organization_id;

        // prevent duplicate review per application
        $existing = OrganizationReview::where('application_id', $app->id)->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Already reviewed.',
                'data' => ['review' => $existing],
            ]);
        }

        $review = OrganizationReview::create([
            'application_id' => $app->id,
            'organization_id' => $orgId,
            'volunteer_user_id' => $user->id,
            ...$data,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Organization reviewed.',
            'data' => ['review' => $review],
        ], 201);
    }

    public function myReviews(Request $request)
    {
        $user = $request->user();
        abort_if($user->type !== 'volunteer', 403);

        $items = OrganizationReview::where('volunteer_user_id', $user->id)
            ->latest('id')
            ->paginate(20);

        return response()->json(['success'=>true,'message'=>'','data'=>$items]);
    }
}