<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class VolunteerDirectoryController extends Controller
{
    public function orgIndex(Request $request)
    {
        $user = $request->user();

        abort_if($user->type !== 'organization', 403, 'Only organizations can access volunteer directory.');

        $q = User::query()
            ->where('type', 'volunteer')
            ->where('status', 'approved');

        if ($request->filled('city')) {
            $q->whereHas('volunteerProfile', function ($sub) use ($request) {
                $sub->where('city', 'like', '%' . $request->city . '%');
            });
        }

        $items = $q->with('volunteerProfile')->latest('id')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }

    public function adminIndex(Request $request)
    {
        $user = $request->user();

        abort_if($user->type !== 'staff', 403, 'Only staff can access admin volunteer directory.');

        $q = User::query()->where('type', 'volunteer');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('city')) {
            $q->whereHas('volunteerProfile', function ($sub) use ($request) {
                $sub->where('city', 'like', '%' . $request->city . '%');
            });
        }

        $items = $q->with('volunteerProfile')->latest('id')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }
}