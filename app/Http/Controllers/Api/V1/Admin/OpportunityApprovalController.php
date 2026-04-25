<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Opportunity;
use App\Models\OpportunityApproval;

class OpportunityApprovalController extends Controller
{
    private function ensureApprover(Request $request): void
    {
        $user = $request->user();

        if ($user->status !== 'approved' || $user->type !== 'staff') {
            abort(403, 'Only approved staff can approve opportunities.');
        }

        $hasRole = DB::table('role_user')
            ->join('roles','roles.id','=','role_user.role_id')
            ->where('role_user.user_id', $user->id)
            ->where('roles.name', 'opportunity_approval_officer')
            ->exists();

        abort_unless($hasRole, 403, 'Missing role: opportunity_approval_officer');
    }

    public function pending(Request $request)
    {
        $this->ensureApprover($request);

        $items = Opportunity::with('organization')
            ->where('status', 'pending_approval')
            ->latest('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $this->ensureApprover($request);

        $data = $request->validate([
            'notes' => ['nullable','string','max:5000'],
        ]);

        DB::transaction(function () use ($request, $id, $data) {
            $opp = Opportunity::lockForUpdate()->findOrFail($id);

            if ($opp->status !== 'pending_approval') {
                abort(422, 'Opportunity is not pending approval.');
            }

            OpportunityApproval::create([
                'opportunity_id' => $opp->id,
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $opp->update(['status' => 'approved']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Opportunity approved.',
            'data' => null,
        ]);
    }

    public function reject(Request $request, $id)
    {
        $this->ensureApprover($request);

        $data = $request->validate([
            'notes' => ['required','string','max:5000'],
        ]);

        DB::transaction(function () use ($request, $id, $data) {
            $opp = Opportunity::lockForUpdate()->findOrFail($id);

            if ($opp->status !== 'pending_approval') {
                abort(422, 'Opportunity is not pending approval.');
            }

            OpportunityApproval::create([
                'opportunity_id' => $opp->id,
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'notes' => $data['notes'],
            ]);

            $opp->update(['status' => 'rejected']);
        });

        return response()->json([
            'success' => true,
            'message' => 'Opportunity rejected.',
            'data' => null,
        ]);
    }
    public function destroy(Request $request, $id)
{
    $this->ensureApprover($request); // أو غيّرها إلى ensureAdmin حسب نظامك
    // إذا تحب role مستقل للحذف مثل content_moderator/admin، قلّي

    $opp = \App\Models\Opportunity::findOrFail($id);
    $opp->delete();

    return response()->json([
        'success' => true,
        'message' => 'Opportunity deleted by admin.',
        'data' => null,
    ]);
}
}