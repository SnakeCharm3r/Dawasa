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

        $workflowDepartment = User::query()
            ->where('email', 'requester@hq.test')
            ->first()?->department ?? $department;

        $lineManager = $this->seedWorkflowUser([
            'first_name' => 'Neema',
            'last_name' => 'Manager',
            'name' => 'Neema Manager',
            'email' => 'line_manager@hq.test',
            'job_title' => 'Line Manager',
            'is_line_manager' => true,
        ], $workflowDepartment, 'line_manager');

        $this->seedWorkflowUser([
            'first_name' => 'Juma',
            'last_name' => 'Storekeeper',
            'name' => 'Juma Storekeeper',
            'email' => 'storekeeper@hq.test',
            'job_title' => 'Storekeeper',
            'is_line_manager' => false,
            'line_manager_id' => $lineManager->id,
        ], $workflowDepartment, 'storekeeper');

        $this->seedWorkflowUser([
            'first_name' => 'Asha',
            'last_name' => 'Receiving',
            'name' => 'Asha Receiving',
            'email' => 'receiving@hq.test',
            'job_title' => 'Receiving Officer',
            'is_line_manager' => false,
            'line_manager_id' => $lineManager->id,
        ], $workflowDepartment, 'receiving_officer');
    }

    private function seedWorkflowUser(array $attributes, Department $department, string $role): User
    {
        $user = User::firstOrNew(['email' => $attributes['email']]);
        $user->fill([
            ...$attributes,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        if (! $user->exists) {
            $user->password = Hash::make('password');
        }

        $user->save();
        $user->syncRoles([$role]);

        return $user;
    }
}
