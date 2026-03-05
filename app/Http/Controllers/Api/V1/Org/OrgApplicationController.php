<?php

namespace App\Http\Controllers\Api\V1\Org;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Application;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\ApplicationStatusNotification;
use App\Notifications\NewConversationNotification;

class OrgApplicationController extends Controller
{
    private function ensureCoordinator(Request $request): Organization
    {
        $user = $request->user();

        if ($user->status !== 'approved' || $user->type !== 'organization') {
            abort(403, 'Only approved organizations can manage applicants.');
        }

        $hasRole = DB::table('role_user')
            ->join('roles','roles.id','=','role_user.role_id')
            ->where('role_user.user_id', $user->id)
            ->where('roles.name', 'org_volunteer_coordinator')
            ->exists();

        if (!$hasRole) abort(403, 'Missing role: org_volunteer_coordinator');

        $org = Organization::where('user_id', $user->id)->first();
        abort_if(!$org, 422, 'Organization profile not found.');

        return $org;
    }

    public function index(Request $request)
    {
        $org = $this->ensureCoordinator($request);

        $q = Application::query()
            ->with(['opportunity', 'volunteer'])
            ->whereHas('opportunity', fn($qq) => $qq->where('organization_id', $org->id));

        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('opportunity_id')) $q->where('opportunity_id', $request->opportunity_id);

        $items = $q->latest('id')->paginate(20);

        return response()->json(['success'=>true,'message'=>'','data'=>$items]);
    }

    // Applicants for one opportunity
    public function applicants(Request $request, $id)
    {
        $org = $this->ensureCoordinator($request);

        $opp = Opportunity::where('organization_id', $org->id)->findOrFail($id);

        $items = Application::with('volunteer')
            ->where('opportunity_id', $opp->id)
            ->latest('id')
            ->paginate(20);

        return response()->json(['success'=>true,'message'=>'','data'=>$items]);
    }

    public function accept(Request $request, $id)
    {
        $org = $this->ensureCoordinator($request);

        DB::transaction(function () use ($request, $org, $id) {

            $app = Application::with('opportunity')->lockForUpdate()->findOrFail($id);

            // ensure app belongs to this org
            if ($app->opportunity->organization_id !== $org->id) abort(403);

            if ($app->status !== 'pending') abort(422, 'Application is not pending.');

            $app->update([
                'status' => 'accepted',
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ]);

            // Auto create conversation if not exists
            $conv = Conversation::firstOrCreate(
                ['application_id' => $app->id],
                [
                    'organization_id' => $org->id,
                    'volunteer_user_id' => $app->volunteer_user_id,
                ]
            );

            // Notify volunteer
            $volunteer = User::find($app->volunteer_user_id);
            if ($volunteer) {
                $volunteer->notify(new ApplicationStatusNotification([
                    'type' => 'application_status',
                    'application_id' => $app->id,
                    'status' => 'accepted',
                    'message' => 'Your application was accepted.',
                ]));

                $volunteer->notify(new NewConversationNotification([
                    'type' => 'new_conversation',
                    'conversation_id' => $conv->id,
                    'application_id' => $app->id,
                    'message' => 'A chat has been opened for your accepted application.',
                ]));
            }
        });

        return response()->json(['success'=>true,'message'=>'Application accepted.','data'=>null]);
    }

    public function reject(Request $request, $id)
    {
        $org = $this->ensureCoordinator($request);

        $data = $request->validate([
            'reason' => ['nullable','string','max:2000'],
        ]);

        DB::transaction(function () use ($request, $org, $id, $data) {

            $app = Application::with('opportunity')->lockForUpdate()->findOrFail($id);

            if ($app->opportunity->organization_id !== $org->id) abort(403);

            if ($app->status !== 'pending') abort(422, 'Application is not pending.');

            $app->update([
                'status' => 'rejected',
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
            ]);

            // Notify volunteer
            $volunteer = User::find($app->volunteer_user_id);
            if ($volunteer) {
                $volunteer->notify(new ApplicationStatusNotification([
                    'type' => 'application_status',
                    'application_id' => $app->id,
                    'status' => 'rejected',
                    'message' => 'Your application was rejected.',
                    'reason' => $data['reason'] ?? null,
                ]));
            }
        });

        return response()->json(['success'=>true,'message'=>'Application rejected.','data'=>null]);
    }
}