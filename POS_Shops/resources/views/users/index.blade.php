<x-app-layout>
    <x-page-header title="User Management" subtitle="Manage cashiers and their permissions" />

    <div class="mb-4 flex flex-col sm:flex-row gap-3">
        <form method="GET" class="flex-1 flex gap-3">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name or email..."
                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm" />
            <select name="role" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                <option value="">All Roles</option>
                <option value="owner" {{ request('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                <option value="cashier" {{ request('role') === 'cashier' ? 'selected' : '' }}>Cashier</option>
            </select>
            <x-button type="submit" variant="secondary">Filter</x-button>
        </form>
    </div>

    <x-card>
        <x-data-table :headers="['Name', 'Email', 'Role', 'Status', 'Actions']">
            @foreach($users as $user)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $user->email }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <span class="capitalize {{ $user->isOwner() ? 'font-semibold text-blue-700' : '' }}">{{ $user->role }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($user->is_active)
                            <x-status-badge status="success">Active</x-status-badge>
                        @else
                            <x-status-badge status="danger">Inactive</x-status-badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('users.show', $user) }}" class="text-blue-600 hover:text-blue-800">View</a>
                            @if(!$user->isOwner())
                                <span class="text-gray-300">|</span>
                                <a href="{{ route('users.permissions', $user) }}" class="text-blue-600 hover:text-blue-800">Permissions</a>
                                <span class="text-gray-300">|</span>
                                <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="{{ $user->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}">
                                        {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-data-table>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </x-card>
</x-app-layout>
