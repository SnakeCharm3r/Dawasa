<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions organized by module
        $permissions = [
            // User Management
            'users.view' => 'View Users',
            'users.create' => 'Create Users',
            'users.edit' => 'Edit Users',
            'users.delete' => 'Delete Users',
            'users.assign_roles' => 'Assign Roles to Users',
            'users.activate' => 'Activate/Deactivate Users',

            // Role & Permission Management
            'roles.view' => 'View Roles',
            'roles.create' => 'Create Roles',
            'roles.edit' => 'Edit Roles',
            'roles.delete' => 'Delete Roles',
            'permissions.view' => 'View Permissions',
            'permissions.assign' => 'Assign Permissions to Roles',

            // System Preferences
            'system_preferences.view' => 'View System Preferences',
            'system_preferences.edit' => 'Edit System Preferences',
            'system_preferences.manage_logo' => 'Manage Logo',
            'system_preferences.manage_favicon' => 'Manage Favicon',

            // Patient Management
            'patients.view' => 'View Patients',
            'patients.create' => 'Create Patients',
            'patients.edit' => 'Edit Patients',
            'patients.delete' => 'Delete Patients',
            'patients.view_all' => 'View All Patients',
            'patients.view_assigned' => 'View Assigned Patients Only',

            // Patient Assessments
            'assessments.view' => 'View Assessments',
            'assessments.create' => 'Create Assessments',
            'assessments.edit' => 'Edit Assessments',
            'assessments.delete' => 'Delete Assessments',

            // Vital Signs
            'vitals.view' => 'View Vital Signs',
            'vitals.create' => 'Create Vital Signs',
            'vitals.edit' => 'Edit Vital Signs',

            // Prescriptions
            'prescriptions.view' => 'View Prescriptions',
            'prescriptions.create' => 'Create Prescriptions',
            'prescriptions.edit' => 'Edit Prescriptions',
            'prescriptions.delete' => 'Delete Prescriptions',

            // Procedures
            'procedures.view' => 'View Procedures',
            'procedures.create' => 'Create Procedures',
            'procedures.edit' => 'Edit Procedures',
            'procedures.delete' => 'Delete Procedures',
            'procedures.implement' => 'Implement Procedures',

            // Medication Administration
            'medications.view' => 'View Medications',
            'medications.administer' => 'Administer Medications',
            'medications.log' => 'Log Medication Administration',

            // Appointments
            'appointments.view' => 'View Appointments',
            'appointments.create' => 'Create Appointments',
            'appointments.edit' => 'Edit Appointments',
            'appointments.delete' => 'Delete Appointments',

            // Billing & Payments
            'billing.view' => 'View Billing',
            'billing.create' => 'Create Bills',
            'billing.edit' => 'Edit Bills',
            'payments.process' => 'Process Payments',
            'payments.refund' => 'Process Refunds',
            'receipts.print' => 'Print Receipts',
            'receipts.view' => 'View Receipts',

            // Inventory
            'inventory.view' => 'View Inventory',
            'inventory.create' => 'Add Inventory Items',
            'inventory.edit' => 'Edit Inventory Items',
            'inventory.delete' => 'Delete Inventory Items',
            'inventory.adjust_stock' => 'Adjust Stock Levels',
            'inventory.view_low_stock' => 'View Low Stock Alerts',

            // Reports
            'reports.view_sales' => 'View Sales Reports',
            'reports.view_patients' => 'View Patient Reports',
            'reports.view_staff' => 'View Staff Performance Reports',
            'reports.view_financial' => 'View Financial Reports',
            'reports.view_inventory' => 'View Inventory Reports',
            'reports.generate' => 'Generate Reports',
            'reports.export' => 'Export Reports',

            // Discharge
            'discharge.view' => 'View Discharge Records',
            'discharge.create' => 'Discharge Patients',
            'discharge.edit' => 'Edit Discharge Records',

            // Dashboard Access
            'dashboard.view_admin' => 'View Admin Dashboard',
            'dashboard.view_manager' => 'View Manager Dashboard',
            'dashboard.view_doctor' => 'View Doctor Dashboard',
            'dashboard.view_nurse' => 'View Nurse Dashboard',
            'dashboard.view_cashier' => 'View Cashier Dashboard',
        ];

        // Create all permissions
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                ['guard_name' => 'web']
            );
        }

        // Create Roles
        $systemAdminRole = Role::firstOrCreate(['name' => 'System Admin', 'guard_name' => 'web']);
        $hospitalManagerRole = Role::firstOrCreate(['name' => 'Hospital Manager', 'guard_name' => 'web']);
        $doctorRole = Role::firstOrCreate(['name' => 'Doctor', 'guard_name' => 'web']);
        $nurseRole = Role::firstOrCreate(['name' => 'Nurse', 'guard_name' => 'web']);
        $cashierRole = Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']);

        // System Admin - Full Access
        $systemAdminRole->syncPermissions(Permission::all());

        // Hospital Manager Permissions
        $hospitalManagerPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.activate',
            'roles.view',
            'permissions.view',
            'system_preferences.view',
            'patients.view', 'patients.create', 'patients.edit', 'patients.view_all',
            'assessments.view',
            'vitals.view',
            'prescriptions.view',
            'procedures.view',
            'medications.view',
            'appointments.view', 'appointments.create', 'appointments.edit', 'appointments.delete',
            'billing.view', 'billing.create', 'billing.edit', 'payments.process', 'payments.refund', 'receipts.print', 'receipts.view',
            'inventory.view', 'inventory.create', 'inventory.edit', 'inventory.adjust_stock', 'inventory.view_low_stock',
            'reports.view_sales', 'reports.view_patients', 'reports.view_staff', 'reports.view_financial', 'reports.view_inventory', 'reports.generate', 'reports.export',
            'discharge.view',
            'dashboard.view_manager',
        ];
        $hospitalManagerRole->syncPermissions($hospitalManagerPermissions);

        // Doctor Permissions
        $doctorPermissions = [
            'patients.view', 'patients.create', 'patients.edit', 'patients.view_all',
            'assessments.view', 'assessments.create', 'assessments.edit',
            'vitals.view',
            'prescriptions.view', 'prescriptions.create', 'prescriptions.edit',
            'procedures.view', 'procedures.create', 'procedures.edit',
            'medications.view',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'billing.view',
            'reports.view_patients',
            'discharge.view', 'discharge.create',
            'dashboard.view_doctor',
        ];
        $doctorRole->syncPermissions($doctorPermissions);

        // Nurse Permissions
        $nursePermissions = [
            'patients.view', 'patients.create', 'patients.edit', 'patients.view_assigned',
            'assessments.view',
            'vitals.view', 'vitals.create', 'vitals.edit',
            'prescriptions.view',
            'procedures.view', 'procedures.implement',
            'medications.view', 'medications.administer', 'medications.log',
            'appointments.view', 'appointments.create', 'appointments.edit',
            'reports.view_patients',
            'dashboard.view_nurse',
        ];
        $nurseRole->syncPermissions($nursePermissions);

        // Cashier Permissions
        $cashierPermissions = [
            'patients.view', 'patients.create', 'patients.edit',
            'appointments.view', 'appointments.create',
            'billing.view', 'billing.create', 'billing.edit',
            'payments.process', 'receipts.print', 'receipts.view',
            'reports.view_sales',
            'dashboard.view_cashier',
        ];
        $cashierRole->syncPermissions($cashierPermissions);

        // Create default System Admin user
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@hospital.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'phone' => '0000000000',
                'is_active' => true,
            ]
        );
        $adminUser->syncRoles([$systemAdminRole]);

        // Create default Hospital Manager user
        $managerUser = User::firstOrCreate(
            ['email' => 'manager@hospital.com'],
            [
                'name' => 'Hospital Manager',
                'password' => Hash::make('password'),
                'phone' => '0000000001',
                'is_active' => true,
            ]
        );
        $managerUser->syncRoles([$hospitalManagerRole]);

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->info('Default users created:');
        $this->command->info('- System Admin: admin@hospital.com / password');
        $this->command->info('- Hospital Manager: manager@hospital.com / password');
    }
}
