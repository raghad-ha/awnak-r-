<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Skill;
use App\Models\VolunteerProfile;
use Illuminate\Support\Facades\DB;

class SkillController extends Controller
{
    public function index(Request $request)
    {
        $items = Skill::query()->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        abort_if(!in_array($user->type, ['staff']), 403, 'Only staff can create skills.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:skills,name'],
        ]);

        $skill = Skill::create([
            'name' => $data['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Skill created.',
            'data' => [
                'skill' => $skill,
            ],
        ], 201);
    }

    public function syncMySkills(Request $request)
    {
        $user = $request->user();

        abort_if($user->type !== 'volunteer', 403, 'Only volunteers can update skills.');

        $data = $request->validate([
            'skill_ids' => ['required', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ]);

        $profile = VolunteerProfile::firstOrCreate(['user_id' => $user->id]);

        DB::table('volunteer_skill')->where('volunteer_profile_id', $profile->id)->delete();

        foreach ($data['skill_ids'] as $skillId) {
            DB::table('volunteer_skill')->insert([
                'volunteer_profile_id' => $profile->id,
                'skill_id' => $skillId,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Volunteer skills updated.',
            'data' => [
                'skill_ids' => $data['skill_ids'],
            ],
        ]);
    }
}