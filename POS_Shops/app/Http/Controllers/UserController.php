<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function toggleActive(User $user)
    {
        if ($user->isOwner()) {
            return back()->with('error', 'Cannot deactivate an owner account.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User {$user->name} has been {$status}.");
    }

    public function editPermissions(User $user)
    {
        if ($user->isOwner()) {
            return redirect()->route('users.index')->with('error', 'Owner permissions cannot be modified.');
        }

        $availablePermissions = [
            'view_inventory' => 'View Inventory',
            'manage_inventory' => 'Manage Inventory (Add/Edit Products)',
            'view_categories' => 'View Categories',
            'view_ledger' => 'View Daily Ledger',
            'manage_ledger' => 'Manage Ledger Entries',
            'process_sales' => 'Process Sales',
        ];

        return view('users.permissions', compact('user', 'availablePermissions'));
    }

    public function updatePermissions(Request $request, User $user)
    {
        if ($user->isOwner()) {
            return redirect()->route('users.index')->with('error', 'Owner permissions cannot be modified.');
        }

        $validated = $request->validate([
            'permissions' => 'array',
            'permissions.*' => ['string', Rule::in([
                'view_inventory',
                'manage_inventory',
                'view_categories',
                'view_ledger',
                'manage_ledger',
                'process_sales',
            ])],
        ]);

        $user->update(['permissions' => $validated['permissions'] ?? []]);

        return redirect()->route('users.index')->with('success', "Permissions updated for {$user->name}.");
    }
}
