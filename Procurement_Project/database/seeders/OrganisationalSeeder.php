<?php

namespace Database\Seeders;

use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\EntityBudget;
use App\Models\FinancialYear;
use App\Models\PurchaseRequisition;
use App\Models\SupplierCategory;
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
            'ceo',
            'accountant',
            'procurement_officer',
            'supplier',
            'department_head',
            'requester',
            'auditor',
            'line_manager',
            'storekeeper',
            'receiving_officer',
        ] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        foreach ([
            'GOODS' => 'General Goods',
            'ICT' => 'ICT Equipment and Services',
            'OFFICE' => 'Office Supplies and Furniture',
            'WORKS' => 'Works and Maintenance',
            'CONSULT' => 'Consultancy Services',
            'LOGISTICS' => 'Transport and Logistics',
        ] as $code => $name) {
            SupplierCategory::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
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

        $operationsDepartment = Department::updateOrCreate(
            ['business_entity_id' => $entity->id, 'code' => 'OPS'],
            ['name' => 'Operations', 'is_active' => true],
        );

        $humanResourcesDepartment = Department::updateOrCreate(
            ['business_entity_id' => $entity->id, 'code' => 'HR'],
            ['name' => 'Human Resources', 'is_active' => true],
        );

        $hrLineManager = User::firstOrNew(['email' => 'hr@hq.test']);
        $hrLineManager->fill([
            'first_name' => 'Hawa',
            'last_name' => 'Rasilimali',
            'name' => 'Hawa Rasilimali',
            'department_id' => $humanResourcesDepartment->id,
            'job_title' => 'Human Resources Line Manager',
            'is_line_manager' => true,
            'is_active' => true,
        ]);
        if (! $hrLineManager->exists || app()->environment('local', 'testing')) {
            $hrLineManager->password = Hash::make('password');
        }
        $hrLineManager->save();
        $hrLineManager->syncRoles(['department_head', 'line_manager']);

        $operationsLineManager = User::firstOrNew(['email' => 'line_manager@hq.test']);
        $operationsLineManager->fill([
            'first_name' => 'Neema',
            'last_name' => 'Manager',
            'name' => 'Neema Manager',
            'department_id' => $operationsDepartment->id,
            'job_title' => 'Operations Line Manager',
            'is_line_manager' => true,
            'is_active' => true,
        ]);
        if (! $operationsLineManager->exists || app()->environment('local', 'testing')) {
            $operationsLineManager->password = Hash::make('password');
        }
        $operationsLineManager->save();
        $operationsLineManager->syncRoles(['department_head', 'line_manager']);

        $requester = $this->seedWorkflowUser([
            'first_name' => 'Rehema',
            'last_name' => 'Requester',
            'name' => 'Rehema Requester',
            'email' => 'requester@hq.test',
            'job_title' => 'Department Requester',
            'is_line_manager' => false,
            'line_manager_id' => $operationsLineManager->id,
        ], $operationsDepartment, 'requester');

        PurchaseRequisition::query()
            ->where('requester_id', $requester->id)
            ->whereIn('status', [
                PurchaseRequisition::STATUS_DRAFT,
                PurchaseRequisition::STATUS_SUBMITTED,
                PurchaseRequisition::STATUS_RETURNED,
            ])
            ->update([
                'department_id' => $operationsDepartment->id,
                'business_entity_id' => $operationsDepartment->business_entity_id,
                'line_manager_id' => $operationsLineManager->id,
            ]);

        $generalManager = $this->seedWorkflowUser([
            'first_name' => 'General',
            'last_name' => 'Manager',
            'name' => 'General Manager',
            'email' => 'gm@hq.test',
            'job_title' => 'General Manager',
            'is_line_manager' => false,
        ], $department, 'gm');

        $accountant = $this->seedWorkflowUser([
            'first_name' => 'Amina',
            'last_name' => 'Accountant',
            'name' => 'Amina Accountant',
            'email' => 'accountant@hq.test',
            'job_title' => 'Accountant',
            'is_line_manager' => false,
        ], $department, 'accountant');

        $this->seedWorkflowUser([
            'first_name' => 'Procurement',
            'last_name' => 'Officer',
            'name' => 'Procurement Officer',
            'email' => 'procurement@hq.test',
            'job_title' => 'Procurement Officer',
            'is_line_manager' => false,
        ], $department, 'procurement_officer');

        $this->seedWorkflowUser([
            'first_name' => 'Chief',
            'last_name' => 'Executive',
            'name' => 'Chief Executive Officer',
            'email' => 'ceo@hq.test',
            'job_title' => 'Chief Executive Officer',
            'is_line_manager' => false,
        ], $department, 'ceo');

        $this->seedWorkflowUser([
            'first_name' => 'Juma',
            'last_name' => 'Storekeeper',
            'name' => 'Juma Storekeeper',
            'email' => 'storekeeper@hq.test',
            'job_title' => 'Storekeeper',
            'is_line_manager' => false,
            'line_manager_id' => $operationsLineManager->id,
        ], $operationsDepartment, 'storekeeper');

        $this->seedWorkflowUser([
            'first_name' => 'Asha',
            'last_name' => 'Receiving',
            'name' => 'Asha Receiving',
            'email' => 'receiving@hq.test',
            'job_title' => 'Receiving Officer',
            'is_line_manager' => false,
            'line_manager_id' => $operationsLineManager->id,
        ], $operationsDepartment, 'receiving_officer');

        $year = now()->year;
        $financialYear = FinancialYear::updateOrCreate(
            ['name' => 'FY'.$year],
            [
                'start_date' => "{$year}-01-01",
                'end_date' => "{$year}-12-31",
                'is_active' => true,
            ],
        );

        FinancialYear::query()
            ->where('id', '<>', $financialYear->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        EntityBudget::updateOrCreate(
            [
                'business_entity_id' => $entity->id,
                'financial_year_id' => $financialYear->id,
            ],
            [
                'proposed_amount' => 250_000_000,
                'approved_amount' => 250_000_000,
                'committed_amount' => 43_000_000,
                'spent_amount' => 18_000_000,
                'available_amount' => 189_000_000,
                'status' => EntityBudget::STATUS_APPROVED,
                'proposed_by' => $accountant->id,
                'submitted_at' => now(),
                'approved_by' => $generalManager->id,
                'approved_at' => now(),
                'approval_comments' => 'Approved current budget for the Procurement Group demo entity.',
                'notes' => 'Seeded current-year budget for demonstrations and workflow testing.',
            ],
        );

        User::query()
            ->whereNotNull('line_manager_id')
            ->with('lineManager.roles')
            ->get()
            ->each(function (User $user): void {
                $lineManager = $user->assignedLineManagerInDepartment();
                if (! $lineManager) {
                    return;
                }

                PurchaseRequisition::query()
                    ->where('requester_id', $user->id)
                    ->whereIn('status', [
                        PurchaseRequisition::STATUS_DRAFT,
                        PurchaseRequisition::STATUS_SUBMITTED,
                        PurchaseRequisition::STATUS_PENDING_GM_APPROVAL,
                        PurchaseRequisition::STATUS_RETURNED,
                    ])
                    ->update([
                        'department_id' => $user->department_id,
                        'business_entity_id' => $user->department->business_entity_id,
                        'line_manager_id' => $lineManager->id,
                    ]);
            });
    }

    private function seedWorkflowUser(array $attributes, Department $department, string $role): User
    {
        $user = User::firstOrNew(['email' => $attributes['email']]);
        $user->fill([
            ...$attributes,
            'department_id' => $department->id,
            'is_active' => true,
        ]);

        if (! $user->exists || app()->environment('local', 'testing')) {
            $user->password = Hash::make('password');
        }

        $user->save();
        $user->syncRoles([$role]);

        return $user;
    }
}
