<?php

namespace Database\Seeders;

use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class OrganisationalSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $entity = BusinessEntity::create([
            'name' => 'Procurement Group',
            'code' => 'PROC001',
            'is_active' => true,
        ]);

        $department = Department::create([
            'business_entity_id' => $entity->id,
            'name' => 'Procurement',
            'code' => 'PROC',
            'is_active' => true,
        ]);

        $superAdmin = User::factory()->create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'name' => 'Super Admin',
            'email' => 'super_admin@example.com',
            'department_id' => $department->id,
            'job_title' => 'Super Admin',
            'is_line_manager' => true,
            'is_active' => true,
        ]);

        foreach ([
            'super_admin',
            'gm',
            'accountant',
            'procurement_officer',
            'department_head',
            'requester',
            'auditor',
        ] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $superAdmin->assignRole('super_admin');
    }
}
