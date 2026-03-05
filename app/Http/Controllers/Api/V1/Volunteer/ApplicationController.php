<?php

namespace App\Http\Controllers\Api\V1\Volunteer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Models\Opportunity;

class ApplicationController extends Controller
{
    public function apply(Request $request, $id)
    {
        $user = $request->user();

        if ($user->status !== 'approved' || $user->type !== 'volunteer') {
            abort(403, 'Only approved volunteers can apply.');
        }

        $data = $request->validate([
            'message' => ['nullable','string','max:5000'],
        ]);

        $opp = Opportunity::where('status','approved')->findOrFail($id);

        // prevent duplicate (DB unique also protects)
        $existing = Application::where('opportunity_id', $opp->id)
            ->where('volunteer_user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Already applied.',
                'data' => ['application' => $existing],
            ]);
        }

        $app = Application::create([
            'opportunity_id' => $opp->id,
            'volunteer_user_id' => $user->id,
            'status' => 'pending',
            'message' => $data['message'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Application submitted.',
            'data' => ['application' => $app],
        ], 201);
    }

    public function myApplications(Request $request)
    {
        $user = $request->user();

        if ($user->type !== 'volunteer') abort(403);

        $items = Application::with('opportunity.organization')
            ->where('volunteer_user_id', $user->id)
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }
}