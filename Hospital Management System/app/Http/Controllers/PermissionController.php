<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:permissions.view');
    }

    /**
     * Display a listing of permissions
     */
    public function index()
    {
        $permissions = Permission::withCount('roles')->paginate(20);
        return view('permissions.index', compact('permissions'));
    }

    /**
     * Display permissions grouped by module
     */
    public function byModule()
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            return explode('.', $permission->name)[0];
        });

        return view('permissions.by-module', compact('permissions'));
    }

    /**
     * Show roles with a specific permission
     */
    public function roles(Permission $permission)
    {
        $roles = $permission->roles;
        return view('permissions.roles', compact('permission', 'roles'));
    }
}
