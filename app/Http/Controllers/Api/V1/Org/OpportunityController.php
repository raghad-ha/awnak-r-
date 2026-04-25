<?php

namespace App\Http\Controllers\Api\V1\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Opportunity;
use App\Models\Organization;

class OpportunityController extends Controller
{
    private function ensureOrgManager(Request $request): Organization
    {
        $user = $request->user();

        if ($user->status !== 'approved' || $user->type !== 'organization') {
            abort(403, 'Only approved organizations can manage opportunities.');
        }

        // role check (optional but recommended)
        $hasRole = DB::table('role_user')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('role_user.user_id', $user->id)
            ->where('roles.name', 'org_opportunity_manager')
            ->exists();

        if (!$hasRole) {
            abort(403, 'Missing role: org_opportunity_manager');
        }

        $org = Organization::where('user_id', $user->id)->first();
        abort_if(!$org, 422, 'Organization profile not found.');

        return $org;
    }

    public function store(Request $request)
    {
        $org = $this->ensureOrgManager($request);

        $data = $request->validate([
            'title' => ['required','string','max:255'],
            'description' => ['required','string'],
            'city' => ['nullable','string','max:255'],
            'location_text' => ['nullable','string','max:255'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'capacity' => ['nullable','integer','min:1'],
        ]);

        $opp = Opportunity::create([
            'organization_id' => $org->id,
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'city' => $data['city'] ?? null,
            'location_text' => $data['location_text'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'capacity' => $data['capacity'] ?? 1,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Opportunity created (draft).',
            'data' => ['opportunity' => $opp],
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $org = $this->ensureOrgManager($request);

        $opp = Opportunity::where('organization_id', $org->id)->findOrFail($id);

        // Only allow edit while draft/rejected (common rule)
        if (!in_array($opp->status, ['draft', 'rejected'])) {
            abort(403, 'You can edit only draft/rejected opportunities.');
        }

        $data = $request->validate([
            'title' => ['sometimes','required','string','max:255'],
            'description' => ['sometimes','required','string'],
            'city' => ['nullable','string','max:255'],
            'location_text' => ['nullable','string','max:255'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'capacity' => ['nullable','integer','min:1'],
        ]);

        $opp->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Opportunity updated.',
            'data' => ['opportunity' => $opp],
        ]);
    }

    public function submit(Request $request, $id)
    {
        $org = $this->ensureOrgManager($request);

        $opp = Opportunity::where('organization_id', $org->id)->findOrFail($id);

        if (!in_array($opp->status, ['draft', 'rejected'])) {
            abort(403, 'Only draft/rejected can be submitted.');
        }

        $opp->update(['status' => 'pending_approval']);

        return response()->json([
            'success' => true,
            'message' => 'Opportunity submitted for approval.',
            'data' => ['opportunity' => $opp],
        ]);
    }


    public function destroy(Request $request, $id)
{
    $org = $this->ensureOrgManager($request); // نفس اللي تستخدمه في store/update

    $opp = \App\Models\Opportunity::where('organization_id', $org->id)->findOrFail($id);

    // optional rule: don't allow deleting approved opportunities (your choice)
    // if you want allow delete always, remove this condition
    // abort_if($opp->status === 'approved', 403, 'Cannot delete approved opportunity.');

    $opp->delete();

    return response()->json([
        'success' => true,
        'message' => 'Opportunity deleted.',
        'data' => null,
    ]);
}
}