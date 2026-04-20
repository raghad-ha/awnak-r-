<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VolunteerProfile;
use App\Models\Organization;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->type === 'volunteer') {
            $profile = VolunteerProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'birthdate' => null,
                    'gender' => null,
                    'city' => null,
                    'bio' => null,
                    'availability' => null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => '',
                'data' => [
                    'type' => 'volunteer',
                    'user' => $user,
                    'profile' => $profile,
                ],
            ]);
        }

        if ($user->type === 'organization') {
            $organization = Organization::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'org_name' => $user->name,
                    'license_no' => null,
                    'address' => null,
                    'city' => null,
                    'description' => null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => '',
                'data' => [
                    'type' => 'organization',
                    'user' => $user,
                    'profile' => $organization,
                ],
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unsupported user type.',
            'data' => null,
        ], 422);
    }

    public function updateVolunteer(Request $request)
    {
        $user = $request->user();

        abort_if($user->type !== 'volunteer', 403, 'Only volunteers can update volunteer profile.');

        $data = $request->validate([
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'availability' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = VolunteerProfile::firstOrCreate(['user_id' => $user->id]);
        $profile->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Volunteer profile updated.',
            'data' => [
                'profile' => $profile->fresh(),
            ],
        ]);
    }

    public function updateOrganization(Request $request)
    {
        $user = $request->user();

        abort_if($user->type !== 'organization', 403, 'Only organizations can update organization profile.');

        $data = $request->validate([
            'org_name' => ['required', 'string', 'max:255'],
            'license_no' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
        ]);

        $organization = Organization::firstOrCreate(['user_id' => $user->id]);
        $organization->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Organization profile updated.',
            'data' => [
                'profile' => $organization->fresh(),
            ],
        ]);
    }
}