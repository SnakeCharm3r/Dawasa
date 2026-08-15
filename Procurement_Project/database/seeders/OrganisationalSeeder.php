<?php

namespace Database\Seeders;

use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class OrganisationalSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach ([
            'super_admin',
            'gm',
            'accountant',
            'procurement_officer',
            'department_head',
            'requester',
            'auditor',
            'line_manager',
            'storekeeper',
            'receiving_officer',
        ] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        $entity = BusinessEntity::updateOrCreate(
            ['code' => 'PROC001'],
            ['name' => 'Procurement Group', 'is_active' => true],
        );

        $department = Department::updateOrCreate(
            ['business_entity_id' => $entity->id, 'code' => 'PROC'],
            ['name' => 'Procurement', 'is_active' => true],
        );

        $superAdmin = User::firstOrNew(['email' => 'super_admin@example.com']);
        $superAdmin->fill([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'name' => 'Super Admin',
            'department_id' => $department->id,
            'job_title' => 'Super Admin',
            'is_line_manager' => true,
            'is_active' => true,
        ]);

        if (! $superAdmin->exists) {
            $superAdmin->password = Hash::make('password');
        }

        $superAdmin->save();

        $superAdmin->syncRoles(['super_admin']);
    }
}
