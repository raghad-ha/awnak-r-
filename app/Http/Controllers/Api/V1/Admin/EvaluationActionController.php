<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EvaluationAction;
use App\Models\User;

class EvaluationActionController extends Controller
{
    private function ensureEvaluationManager(Request $request): void
    {
        $user = $request->user();

        if ($user->status !== 'approved' || $user->type !== 'staff') {
            abort(403, 'Only approved staff can do evaluation actions.');
        }

        $hasRole = DB::table('role_user')
            ->join('roles','roles.id','=','role_user.role_id')
            ->where('role_user.user_id', $user->id)
            ->where('roles.name', 'evaluation_manager')
            ->exists();

        abort_unless($hasRole, 403, 'Missing role: evaluation_manager');
    }

    public function store(Request $request, $id)
    {
        $this->ensureEvaluationManager($request);

        $data = $request->validate([
            'action' => ['required','in:warn,suspend,block'],
            'reason' => ['required','string','max:5000'],
        ]);

        $volunteer = User::findOrFail($id);
        abort_if($volunteer->type !== 'volunteer', 422, 'Target user is not a volunteer.');

        $act = EvaluationAction::create([
            'volunteer_user_id' => $volunteer->id,
            'action' => $data['action'],
            'reason' => $data['reason'],
            'decided_by' => $request->user()->id,
        ]);

        // optional: update user.status when suspend/block
        if ($data['action'] === 'block') {
            $volunteer->update(['status' => 'blocked']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Action recorded.',
            'data' => ['action' => $act],
        ], 201);
    }

    public function index(Request $request, $id)
    {
        $this->ensureEvaluationManager($request);

        $items = EvaluationAction::where('volunteer_user_id', $id)
            ->latest('id')
            ->paginate(20);

        return response()->json(['success'=>true,'message'=>'','data'=>$items]);
    }
}