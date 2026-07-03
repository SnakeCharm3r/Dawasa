<x-app-layout>
    <x-page-header title="User Details" :subtitle="$user->name" />

    <div class="mb-4">
        <a href="{{ route('users.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; Back to Users</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card title="Profile Information">
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Role</dt>
                    <dd class="mt-1">
                        <span class="capitalize text-sm font-semibold {{ $user->isOwner() ? 'text-blue-700' : 'text-gray-900' }}">{{ $user->role }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                        @if($user->is_active)
                            <x-status-badge status="success">Active</x-status-badge>
                        @else
                            <x-status-badge status="danger">Inactive</x-status-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Joined</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->created_at->format('F j, Y') }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card title="Permissions">
            @if($user->isOwner())
                <p class="text-sm text-gray-500">Owner has full access to all features.</p>
            @else
                @php
                $permissionLabels = [
                    'view_inventory' => 'View Inventory',
                    'manage_inventory' => 'Manage Inventory',
                    'view_ledger' => 'View Daily Ledger',
                    'manage_ledger' => 'Manage Ledger Entries',
                    'process_sales' => 'Process Sales',
                ];
                @endphp
                @if(!empty($user->permissions))
                    <ul class="space-y-2">
                        @foreach($user->permissions as $permission)
                            <li class="flex items-center gap-2 text-sm text-gray-900">
                                <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ $permissionLabels[$permission] ?? $permission }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500">No permissions assigned.</p>
                @endif
                <div class="mt-4">
                    <a href="{{ route('users.permissions', $user) }}">
                        <x-button variant="secondary">Edit Permissions</x-button>
                    </a>
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
