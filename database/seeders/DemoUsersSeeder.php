<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Create / update demo users
        $admin = User::updateOrCreate(
            ['email' => 'officer@test.com'],
            [
                'name' => 'Approval Officer',
                'phone' => '11111111',
                'password' => Hash::make('password123'),
                'type' => 'staff',
                'status' => 'approved',
            ]
        );

        $org = User::updateOrCreate(
            ['email' => 'org1@test.com'],
            [
                'name' => 'Test Org User',
                'phone' => '22222222',
                'password' => Hash::make('password123'),
                'type' => 'organization',
                'status' => 'approved',
            ]
        );

        $vol = User::updateOrCreate(
            ['email' => 'volunteer1@test.com'],
            [
                'name' => 'Test Volunteer',
                'phone' => '33333333',
                'password' => Hash::make('password123'),
                'type' => 'volunteer',
                'status' => 'approved',
            ]
        );

        // Map roles to users
        $adminRoles = ['approval_officer','opportunity_approval_officer','evaluation_manager'];
        $orgRoles   = ['org_opportunity_manager','org_volunteer_coordinator'];

        foreach ($adminRoles as $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if ($roleId) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $admin->id, 'role_id' => $roleId],
                    []
                );
            }
        }

        foreach ($orgRoles as $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->value('id');
            if ($roleId) {
                DB::table('role_user')->updateOrInsert(
                    ['user_id' => $org->id, 'role_id' => $roleId],
                    []
                );
            }
        }

        // Volunteer: no roles needed (permissions come from type=volunteer)
    }
}