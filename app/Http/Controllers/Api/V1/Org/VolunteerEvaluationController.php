<?php

namespace App\Http\Controllers\Api\V1\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Application;
use App\Models\Organization;
use App\Models\VolunteerEvaluation;

class VolunteerEvaluationController extends Controller
{
    private function ensureOrgCoordinator(Request $request): Organization
    {
        $user = $request->user();

        if ($user->status !== 'approved' || $user->type !== 'organization') {
            abort(403, 'Only approved organizations can evaluate volunteers.');
        }

        $hasRole = DB::table('role_user')
            ->join('roles','roles.id','=','role_user.role_id')
            ->where('role_user.user_id', $user->id)
            ->where('roles.name', 'org_volunteer_coordinator')
            ->exists();

        abort_unless($hasRole, 403, 'Missing role: org_volunteer_coordinator');

        $org = Organization::where('user_id', $user->id)->first();
        abort_if(!$org, 422, 'Organization profile not found.');

        return $org;
    }

    public function store(Request $request, $id)
    {
        $org = $this->ensureOrgCoordinator($request);

        $data = $request->validate([
            'score'  => ['required','integer','min:1','max:5'],
            'report' => ['nullable','string','max:5000'],
        ]);

        $app = Application::with('opportunity')->findOrFail($id);

        // ensure this application belongs to this org
        abort_if($app->opportunity->organization_id !== $org->id, 403, 'Not your application.');

        // recommended: only evaluate accepted applications
        abort_if($app->status !== 'accepted', 422, 'You can evaluate only accepted applications.');

        // prevent duplicate evaluation for same application
        $existing = VolunteerEvaluation::where('application_id', $app->id)->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Already evaluated.',
                'data' => ['evaluation' => $existing],
            ]);
        }

        $evaluation = VolunteerEvaluation::create([
            'application_id' => $app->id,
            'organization_id' => $org->id,
            'volunteer_user_id' => $app->volunteer_user_id,
            'score' => $data['score'],
            'report' => $data['report'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Volunteer evaluated.',
            'data' => ['evaluation' => $evaluation],
        ], 201);
    }

    // optional listing for org
    public function index(Request $request)
    {
        $org = $this->ensureOrgCoordinator($request);

        $items = VolunteerEvaluation::where('organization_id', $org->id)
            ->latest('id')
            ->paginate(20);

        return response()->json(['success'=>true,'message'=>'','data'=>$items]);
    }
}