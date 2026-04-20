<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use App\Models\VolunteerProfile;

class ProfilesSeeder extends Seeder
{
    public function run(): void
    {
        $orgUser = User::where('email', 'org1@test.com')->first();
        if ($orgUser) {
            Organization::updateOrCreate(
                ['user_id' => $orgUser->id],
                [
                    'org_name' => 'Test Organization',
                    'city' => 'Damascus',
                    'address' => 'Demo Address',
                    'description' => 'Demo organization profile for testing.',
                ]
            );
        }

        $volUser = User::where('email', 'volunteer1@test.com')->first();
        if ($volUser) {
            VolunteerProfile::updateOrCreate(
                ['user_id' => $volUser->id],
                [
                    'city' => 'Damascus',
                    'bio' => 'Demo volunteer profile for testing.',
                    'availability' => 'Weekends',
                ]
            );
        }
    }
}