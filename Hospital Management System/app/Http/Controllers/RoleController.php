<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:roles.view')->only(['index', 'show']);
        $this->middleware('permission:roles.create')->only(['create', 'store']);
        $this->middleware('permission:roles.edit')->only(['edit', 'update']);
        $this->middleware('permission:roles.delete')->only(['destroy']);
        $this->middleware('permission:permissions.assign')->only(['updatePermissions']);
    }

    /**
     * Display a listing of roles
     */
    public function index()
    {
        $roles = Role::withCount('users')->paginate(15);
        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new role
     */
    public function create()
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });
        return view('roles.create', compact('permissions'));
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $permissionIds = $request->permissions;
            $permissions = Permission::whereIn('id', $permissionIds)->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified role
     */
    public function show(Role $role)
    {
        $role->load('permissions', 'users');
        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified role
     */
    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => ['required', 'string', 'unique:roles,name,' . $role->id],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $permissionIds = $request->permissions;
            $permissions = Permission::whereIn('id', $permissionIds)->get();
            $role->syncPermissions($permissions);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified role
     */
    public function destroy(Role $role)
    {
        // Prevent deleting default roles
        $defaultRoles = ['System Admin', 'Hospital Manager', 'Doctor', 'Nurse', 'Cashier'];
        if (in_array($role->name, $defaultRoles)) {
            return redirect()->route('roles.index')
                ->with('error', 'Cannot delete default system roles.');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    /**
     * Update role permissions
     */
    public function updatePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $permissionIds = $request->permissions;
        $permissions = Permission::whereIn('id', $permissionIds)->get();
        $role->syncPermissions($permissions);

        return redirect()->route('roles.show', $role)
            ->with('success', 'Permissions updated successfully.');
    }
}
