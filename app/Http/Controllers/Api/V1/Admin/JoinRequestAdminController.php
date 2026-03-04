<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JoinRequest;
use App\Models\User;

class JoinRequestAdminController extends Controller
{
    private function ensureApprovalOfficer(Request $request): void
    {
        // If you use roles table:
        $isOfficer = DB::table('role_user')
            ->join('roles','roles.id','=','role_user.role_id')
            ->where('role_user.user_id', $request->user()->id)
            ->where('roles.name', 'approval_officer')
            ->exists();

        abort_unless($isOfficer, 403, 'Forbidden');
    }

    public function index(Request $request)
    {
        $this->ensureApprovalOfficer($request);

        $query = JoinRequest::query()->with('files');

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('type')) $query->where('requester_type', $request->type);

        $items = $query->latest('id')->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }

    public function show(Request $request, $id)
    {
        $this->ensureApprovalOfficer($request);

        $jr = JoinRequest::with('files')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => ['join_request' => $jr],
        ]);
    }

    public function approve(Request $request, $id)
    {
        $this->ensureApprovalOfficer($request);

        $data = $request->validate([
            'notes' => ['nullable','string','max:5000'],
        ]);

        DB::transaction(function () use ($request, $id, $data) {
            $jr = JoinRequest::lockForUpdate()->findOrFail($id);

            $jr->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'notes' => $data['notes'] ?? $jr->notes,
            ]);

            User::where('id', $jr->requester_user_id)->update([
                'status' => 'approved',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Join request approved.',
            'data' => null,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $this->ensureApprovalOfficer($request);

        $data = $request->validate([
            'notes' => ['required','string','max:5000'],
        ]);

        DB::transaction(function () use ($request, $id, $data) {
            $jr = JoinRequest::lockForUpdate()->findOrFail($id);

            $jr->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'notes' => $data['notes'],
            ]);

            User::where('id', $jr->requester_user_id)->update([
                'status' => 'rejected',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Join request rejected.',
            'data' => null,
        ]);
    }
}
