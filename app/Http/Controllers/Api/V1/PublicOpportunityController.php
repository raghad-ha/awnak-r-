<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Opportunity;

class PublicOpportunityController extends Controller
{
    public function index(Request $request)
    {
        $q = Opportunity::with('organization')
            ->where('status', 'approved');

        if ($request->filled('city')) {
            $q->where('city', $request->city);
        }

        if ($request->filled('keyword')) {
            $kw = $request->keyword;
            $q->where(function ($qq) use ($kw) {
                $qq->where('title', 'like', "%$kw%")
                   ->orWhere('description', 'like', "%$kw%");
            });
        }

        if ($request->filled('date_from')) {
            $q->whereDate('start_date', '>=', $request->date_from);
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
        $opp = Opportunity::with(['organization', 'approvals'])
            ->where('status', 'approved')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => ['opportunity' => $opp],
        ]);
    }
}